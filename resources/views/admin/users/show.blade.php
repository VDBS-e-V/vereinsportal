@php
    $name = $person?->fullName() ?: $user->email;
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Verwaltung'], ['label' => 'Nutzer', 'href' => route('admin.users.index')], ['label' => $name]];
    $pending = $user->status === \App\Modules\Access\Enums\UserStatus::PendingVerification;
    $isActive = $user->status === \App\Modules\Access\Enums\UserStatus::Active;
    $isDisabled = $user->status === \App\Modules\Access\Enums\UserStatus::Disabled;
    $methodLabels = ['totp' => 'Authenticator-App', 'email' => 'E-Mail-Code'];
@endphp

<x-layouts.app :title="$name" width="wide">
    <x-vdbs.layout.page-header :title="$name" :breadcrumb="$crumbs" eyebrow="Nutzerverwaltung">
        <x-slot:meta><x-vdbs.data.badge :tone="$user->status->tone()">{{ $user->status->label() }}</x-vdbs.data.badge></x-slot:meta>

        @if ($canManage)
            <x-slot:actions>
                @if ($pending)
                    <form method="post" action="{{ route('admin.users.invite', $user) }}">@csrf
                        <x-vdbs.actions.button type="submit" variant="secondary" icon="send">Einladung erneut senden</x-vdbs.actions.button>
                    </form>
                @endif
                @if ($isActive)
                    <form method="post" action="{{ route('admin.users.password-link', $user) }}">@csrf
                        <x-vdbs.actions.button type="submit" variant="secondary" icon="key-round">Passwort-Link senden</x-vdbs.actions.button>
                    </form>
                    @unless ($isSelf)
                        <x-vdbs.actions.button variant="danger" shape="outline" icon="lock" data-vdbs-dialog-open="disable-user">Sperren</x-vdbs.actions.button>
                    @endunless
                @endif
                @if ($isDisabled)
                    <form method="post" action="{{ route('admin.users.enable', $user) }}">@csrf
                        <x-vdbs.actions.button type="submit" icon="check">Entsperren</x-vdbs.actions.button>
                    </form>
                @endif
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="2" align="start">
        <section aria-labelledby="account-heading">
            <h2 id="account-heading" style="font-size:var(--font-size-h3)">Konto</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="E-Mail-Adresse">{{ $user->email }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Status">{{ $user->status->label() }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Angelegt am">{{ $user->created_at?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="E-Mail bestätigt">{{ $user->email_verified_at?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Letzte Anmeldung">{{ $user->last_login_at?->format('d.m.Y, H:i') }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>

        <section aria-labelledby="person-heading">
            <h2 id="person-heading" style="font-size:var(--font-size-h3)">Person</h2>
            @if ($person)
                <x-vdbs.data.detail-list layout="inline">
                    <x-vdbs.data.detail-item label="Name">{{ $person->fullName() }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Geburtsdatum">{{ $person->birth_date?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Telefon">{{ $person->phone }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Anschrift">{{ collect([trim(($person->street ?? '').' '.($person->house_number ?? '')), trim(($person->postal_code ?? '').' '.($person->city ?? ''))])->filter()->implode(', ') }}</x-vdbs.data.detail-item>
                </x-vdbs.data.detail-list>
            @else
                <p>Das Konto ist keiner Person zugeordnet.</p>
            @endif
        </section>
    </x-vdbs.layout.grid>

    <section aria-labelledby="twofactor-heading" style="margin-top:var(--space-8)">
        <h2 id="twofactor-heading" style="font-size:var(--font-size-h3)">Zwei-Faktor-Anmeldung</h2>
        @if (count($twoFactorMethods) > 0)
            <p>
                @foreach ($twoFactorMethods as $method)
                    <x-vdbs.data.badge tone="success">{{ $methodLabels[$method] ?? $method }}</x-vdbs.data.badge>
                @endforeach
                <span style="margin-left:var(--space-2)">{{ $remainingCodes }} von 10 Wiederherstellungscodes übrig</span>
            </p>
            @if ($canManage)
                <x-vdbs.actions.button variant="danger" shape="outline" icon="shield" data-vdbs-dialog-open="reset-two-factor">Zwei-Faktor zurücksetzen</x-vdbs.actions.button>
            @endif
        @else
            <p>Nicht eingerichtet.</p>
        @endif
    </section>

    <section aria-labelledby="roles-heading" style="margin-top:var(--space-8)">
        <h2 id="roles-heading" style="font-size:var(--font-size-h3)">Rollen</h2>

        @if ($currentAssignments->isEmpty())
            <x-vdbs.feedback.empty-state size="s" title="Keine Rolle" icon="users"><p>Dieses Konto hat zurzeit keine gültige Rolle.</p></x-vdbs.feedback.empty-state>
        @else
            <x-vdbs.data.table caption="Gültige Rollen" :columns="[['key' => 'role', 'label' => 'Rolle'], ['key' => 'since', 'label' => 'Seit'], ['key' => 'until', 'label' => 'Bis'], ['key' => 'by', 'label' => 'Vergeben von'], ['key' => 'comment', 'label' => 'Kommentar'], ['key' => 'action', 'label' => 'Aktion']]">
                @foreach ($currentAssignments as $assignment)
                    <tr>
                        <td>{{ $assignment->role?->name }}</td>
                        <td>{{ $assignment->starts_at->format('d.m.Y') }}</td>
                        <td>{{ $assignment->ends_at?->format('d.m.Y') ?? 'unbefristet' }}</td>
                        <td>{{ $assignment->granted_by_user_id ? ($assignment->grantedBy?->person?->fullName() ?: $assignment->grantedBy?->email) : ($assignment->source->value === 'automatic' ? 'automatisch' : '–') }}</td>
                        <td>{{ $assignment->comment }}</td>
                        <td>
                            @if ($canAssign)
                                <x-vdbs.actions.button variant="danger" shape="outline" size="s" data-vdbs-dialog-open="end-role-{{ $assignment->id }}">Beenden</x-vdbs.actions.button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif

        @if ($canAssign && $assignable->isNotEmpty())
            <h3 style="margin-top:var(--space-6);font-size:var(--font-size-h4)">Rolle vergeben</h3>
            <x-vdbs.forms.form :action="route('admin.users.roles.store', $user)" :summary="false">
                <x-vdbs.forms.form-grid :columns="3">
                    <x-vdbs.forms.select name="role_id" label="Rolle" placeholder="Bitte wählen" :required="true" :options="$assignable->pluck('name', 'id')->all()" />
                    <x-vdbs.forms.input name="ends_at" label="Gültig bis" type="date" :optional="true" hint="Leer lassen für unbefristet" />
                    <x-vdbs.forms.input name="comment" label="Kommentar" :optional="true" />
                </x-vdbs.forms.form-grid>
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Rolle vergeben</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @endif

        @if ($pastAssignments->isNotEmpty())
            <x-vdbs.disclosure.accordion style="margin-top:var(--space-6)">
                <x-vdbs.disclosure.accordion-item title="Frühere Rollen ({{ $pastAssignments->count() }})" :level="3">
                    <x-vdbs.data.timeline label="Frühere Rollen">
                        @foreach ($pastAssignments as $assignment)
                            <x-vdbs.data.timeline-item :title="$assignment->role?->name" :time="$assignment->starts_at->format('d.m.Y').' bis '.($assignment->ends_at?->format('d.m.Y') ?? '?')" icon="users">{{ $assignment->comment }}</x-vdbs.data.timeline-item>
                        @endforeach
                    </x-vdbs.data.timeline>
                </x-vdbs.disclosure.accordion-item>
            </x-vdbs.disclosure.accordion>
        @endif
    </section>

    @if ($history !== null)
        <x-admin.history :entries="$history" :href="route('admin.audit.index', ['subject' => 'user:'.$user->id])" />
    @endif

    @if ($canManage && $isActive && ! $isSelf)
        <x-vdbs.overlays.confirm-dialog id="disable-user" title="Konto sperren?" variant="danger" :action="route('admin.users.disable', $user)" confirm-label="Konto sperren">
            <p><strong>{{ $name }}</strong> kann sich danach nicht mehr anmelden, und alle bestehenden Sitzungen werden sofort beendet. Sie können das Konto jederzeit entsperren.</p>
        </x-vdbs.overlays.confirm-dialog>
    @endif

    @if ($canManage && count($twoFactorMethods) > 0)
        <x-vdbs.overlays.confirm-dialog id="reset-two-factor" title="Zwei-Faktor zurücksetzen?" variant="danger" :action="route('admin.users.two-factor.reset', $user)" confirm-label="Zurücksetzen">
            <p>Alle Zwei-Faktor-Methoden und Wiederherstellungscodes von <strong>{{ $name }}</strong> werden entfernt. Das Konto ist danach nur noch durch das Passwort geschützt, bis die Person neue Methoden einrichtet. Nutzen Sie das nur, wenn die Identität sicher feststeht.</p>
        </x-vdbs.overlays.confirm-dialog>
    @endif

    @if ($canAssign)
        @foreach ($currentAssignments as $assignment)
            <x-vdbs.overlays.confirm-dialog id="end-role-{{ $assignment->id }}" title="Rolle beenden?" variant="danger" :action="route('admin.users.roles.destroy', [$user, $assignment])" method="delete" confirm-label="Rolle beenden">
                <p>Die Rolle <strong>{{ $assignment->role?->name }}</strong> von {{ $name }} endet sofort. Die Zuordnung bleibt als Verlauf erhalten.</p>
            </x-vdbs.overlays.confirm-dialog>
        @endforeach
    @endif
</x-layouts.app>
