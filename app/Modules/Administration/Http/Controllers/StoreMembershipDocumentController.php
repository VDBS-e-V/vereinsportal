<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\StoreMembershipDocumentAction;
use App\Modules\Membership\Enums\MembershipDocumentType;
use App\Modules\Membership\Models\Membership;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

final class StoreMembershipDocumentController extends Controller
{
    public function __invoke(
        Request $request,
        Membership $membership,
        AdministrationAccess $access,
        StoreMembershipDocumentAction $storeDocument,
    ): RedirectResponse {
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
            'document_type' => ['required', Rule::enum(MembershipDocumentType::class)],
            'label' => ['nullable', 'string', 'max:150'],
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

        $documentType = MembershipDocumentType::from(
            (string) $validated['document_type'],
        );
        $receivedOn = isset($validated['received_on'])
            ? CarbonImmutable::parse((string) $validated['received_on'])
            : null;

        $storeDocument->execute(
            membership: $membership,
            file: $file,
            documentType: $documentType,
            actor: $actor,
            label: isset($validated['label'])
                ? (string) $validated['label']
                : null,
            receivedOn: $receivedOn,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route('administration.memberships.show', $membership)
            ->with('status', 'Das Dokument wurde der Mitgliedschaft hinzugefügt.')
            ->with('status_type', 'success');
    }
}
