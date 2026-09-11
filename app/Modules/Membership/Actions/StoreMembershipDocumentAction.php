<?php

namespace App\Modules\Membership\Actions;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\MembershipDocumentType;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipDocument;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class StoreMembershipDocumentAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        Membership $membership,
        UploadedFile $file,
        MembershipDocumentType $documentType,
        User $actor,
        ?string $label = null,
        ?CarbonInterface $receivedOn = null,
        ?MembershipDocument $supersedes = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): MembershipDocument {
        $mimeType = $file->getMimeType();
        $extension = match ($mimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => throw ValidationException::withMessages([
                'document' => 'Es sind nur PDF-, JPEG- und PNG-Dateien zulässig.',
            ]),
        };

        $realPath = $file->getRealPath();
        $checksum = is_string($realPath)
            ? hash_file('sha256', $realPath)
            : false;

        if (! is_string($checksum)) {
            throw new RuntimeException('Die Prüfsumme des Dokuments konnte nicht berechnet werden.');
        }

        $fileName = Str::uuid()->toString().'.'.$extension;
        $directory = 'memberships/'.$membership->id.'/documents';
        $storedPath = Storage::disk('local')->putFileAs(
            $directory,
            $file,
            $fileName,
        );

        if (! is_string($storedPath)) {
            throw new RuntimeException('Das Mitgliedschaftsdokument konnte nicht gespeichert werden.');
        }

        try {
            return DB::transaction(function () use (
                $membership,
                $file,
                $documentType,
                $actor,
                $label,
                $receivedOn,
                $supersedes,
                $ipAddress,
                $userAgent,
                $mimeType,
                $checksum,
                $storedPath,
            ): MembershipDocument {
                $lockedMembership = Membership::query()
                    ->whereKey($membership->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $supersedesId = null;

                if ($supersedes instanceof MembershipDocument) {
                    $lockedSupersedes = MembershipDocument::query()
                        ->whereKey($supersedes->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($lockedSupersedes->membership_id !== $lockedMembership->id) {
                        throw ValidationException::withMessages([
                            'document' => 'Das zu ersetzende Dokument gehört nicht zu dieser Mitgliedschaft.',
                        ]);
                    }

                    if ($lockedSupersedes->supersededBy()->exists()) {
                        throw ValidationException::withMessages([
                            'document' => 'Dieses Dokument wurde bereits ersetzt.',
                        ]);
                    }

                    $documentType = $lockedSupersedes->document_type;
                    $label = $lockedSupersedes->label;
                    $supersedesId = $lockedSupersedes->id;
                }

                $originalName = mb_substr(
                    basename($file->getClientOriginalName()),
                    0,
                    255,
                );

                $document = MembershipDocument::query()->create([
                    'membership_id' => $lockedMembership->id,
                    'document_type' => $documentType,
                    'label' => $label !== null && trim($label) !== ''
                        ? trim($label)
                        : null,
                    'disk' => 'local',
                    'path' => $storedPath,
                    'original_name' => $originalName !== ''
                        ? $originalName
                        : 'dokument.'.$file->extension(),
                    'mime_type' => $mimeType,
                    'size_bytes' => $file->getSize(),
                    'sha256' => $checksum,
                    'received_on' => $receivedOn?->toDateString(),
                    'uploaded_by_user_id' => $actor->id,
                    'supersedes_document_id' => $supersedesId,
                ]);

                $this->auditWriter->write(
                    eventKey: $supersedesId === null
                        ? AuditEventCatalog::MEMBERSHIP_DOCUMENT_UPLOADED
                        : AuditEventCatalog::MEMBERSHIP_DOCUMENT_REPLACED,
                    actorType: AuditActorType::User,
                    actorUserId: $actor->id,
                    subjectType: 'membership_document',
                    subjectId: $document->id,
                    newValues: [
                        'membership_id' => $lockedMembership->id,
                        'document_type' => $documentType->value,
                        'size_bytes' => $document->size_bytes,
                        'received_on' => $document->received_on?->toDateString(),
                        'supersedes_document_id' => $supersedesId,
                    ],
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                );

                return $document->refresh();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPath);

            throw $exception;
        }
    }
}
