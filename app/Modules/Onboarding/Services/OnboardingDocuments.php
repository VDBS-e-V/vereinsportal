<?php

namespace App\Modules\Onboarding\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Onboarding\Enums\OnboardingDocumentKind;
use App\Modules\Onboarding\Models\OnboardingDocument;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dokumente des Onboardings (Satzung, Willkommensbrief, Erklärungen) im privaten Speicher. Erlaubt ist nur PDF. Je Art gilt die jüngste
 * Datei; ältere bleiben erhalten (Nachweis, welche Fassung jemand bestätigt hat).
 */
final class OnboardingDocuments
{
    public const DISK = 'local';

    public const MAX_KILOBYTES = 10240;

    public function current(OnboardingDocumentKind $kind): ?OnboardingDocument
    {
        return OnboardingDocument::query()->where('kind', $kind->value)->orderByDesc('id')->first();
    }

    /** @return Collection<int, OnboardingDocument> alle Fassungen, die neueste zuerst */
    public function history(OnboardingDocumentKind $kind): Collection
    {
        return OnboardingDocument::query()->with('uploadedBy.person')->where('kind', $kind->value)->orderByDesc('id')->get();
    }

    public function store(UploadedFile $file, OnboardingDocumentKind $kind, User $by): OnboardingDocument
    {
        if ((string) $file->getMimeType() !== 'application/pdf') {
            throw new DomainException('Erlaubt sind nur PDF-Dateien.');
        }

        $path = 'onboarding-documents/'.$kind->value.'/'.Str::uuid().'.pdf';

        Storage::disk(self::DISK)->putFileAs(dirname($path), $file, basename($path));

        $document = OnboardingDocument::query()->create([
            'kind' => $kind,
            'original_name' => Str::limit($this->cleanName($file->getClientOriginalName()), 200, ''),
            'path' => $path,
            'mime' => 'application/pdf',
            'size' => (int) $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by_user_id' => $by->id,
            'uploaded_at' => now(),
        ]);

        AuditLog::record(AuditEvent::OnboardingDocumentUploaded, $by, null, ['dokument' => $kind->label()]);

        return $document;
    }

    private function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));

        return preg_replace('/[^\p{L}\p{N}\s._()\-]/u', '_', $name) ?: 'dokument.pdf';
    }
}
