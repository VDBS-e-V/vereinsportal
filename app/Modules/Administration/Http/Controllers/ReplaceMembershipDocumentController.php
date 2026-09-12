<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\StoreMembershipDocumentAction;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipDocument;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class ReplaceMembershipDocumentController extends Controller
{
    public function __invoke(
        Request $request,
        Membership $membership,
        MembershipDocument $membershipDocument,
        AdministrationAccess $access,
        StoreMembershipDocumentAction $storeDocument,
    ): RedirectResponse {
        abort_unless($membershipDocument->membership_id === $membership->id, 404);

        $actor = $request->user();
        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::MembershipDocumentsManage,
            ),
            403,
        );

        $validated = $request->validate([
            'received_on' => ['nullable', 'date'],
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:10240',
            ],
        ]);

        $file = $request->file('document');
        abort_unless($file instanceof UploadedFile, 422);

        $receivedOn = isset($validated['received_on'])
            ? CarbonImmutable::parse((string) $validated['received_on'])
            : null;

        $storeDocument->execute(
            membership: $membership,
            file: $file,
            documentType: $membershipDocument->document_type,
            actor: $actor,
            label: $membershipDocument->label,
            receivedOn: $receivedOn,
            supersedes: $membershipDocument,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route('administration.memberships.show', $membership)
            ->with('status', 'Eine neue Dokumentversion wurde gespeichert; die vorherige Version bleibt erhalten.')
            ->with('status_type', 'success');
    }
}
