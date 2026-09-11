<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadMembershipDocumentController extends Controller
{
    public function __invoke(
        Request $request,
        Membership $membership,
        MembershipDocument $membershipDocument,
        AuditWriter $auditWriter,
    ): StreamedResponse {
        abort_unless($membershipDocument->membership_id === $membership->id, 404);
        abort_unless($membershipDocument->disk === 'local', 404);
        abort_unless(
            Storage::disk('local')->exists($membershipDocument->path),
            404,
        );

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $auditWriter->write(
            eventKey: AuditEventCatalog::MEMBERSHIP_DOCUMENT_DOWNLOADED,
            actorType: AuditActorType::User,
            actorUserId: $actor->id,
            subjectType: 'membership_document',
            subjectId: $membershipDocument->id,
            newValues: [
                'membership_id' => $membership->id,
                'document_type' => $membershipDocument->document_type->value,
            ],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return Storage::disk('local')->download(
            $membershipDocument->path,
            $membershipDocument->original_name,
            ['Content-Type' => $membershipDocument->mime_type],
        );
    }
}
