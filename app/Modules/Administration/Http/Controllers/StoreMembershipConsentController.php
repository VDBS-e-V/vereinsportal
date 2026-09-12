<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\RecordMembershipConsentAction;
use App\Modules\Membership\Enums\MembershipConsentSource;
use App\Modules\Membership\Models\Membership;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class StoreMembershipConsentController extends Controller
{
    public function __invoke(
        Request $request,
        Membership $membership,
        AdministrationAccess $access,
        RecordMembershipConsentAction $recordConsent,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::MembershipConsentsManage,
            ),
            403,
        );

        $validated = $request->validate([
            'consent_key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9][a-zA-Z0-9._-]*$/',
            ],
            'label' => ['required', 'string', 'max:150'],
            'version' => ['required', 'string', 'max:50'],
            'source' => ['required', Rule::enum(MembershipConsentSource::class)],
            'granted_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $recordConsent->execute(
            membership: $membership,
            consentKey: (string) $validated['consent_key'],
            label: (string) $validated['label'],
            version: (string) $validated['version'],
            source: MembershipConsentSource::from((string) $validated['source']),
            grantedAt: CarbonImmutable::parse((string) $validated['granted_at']),
            actor: $actor,
            notes: isset($validated['notes'])
                ? (string) $validated['notes']
                : null,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route('administration.memberships.show', $membership)
            ->with('status', 'Der Zustimmungsnachweis wurde gespeichert.')
            ->with('status_type', 'success');
    }
}
