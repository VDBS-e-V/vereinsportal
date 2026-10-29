@php
    $name = $person->fullName();
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Verwaltung'], ['label' => 'Personen', 'href' => route('admin.people.index')], ['label' => $name]];
    $address = collect([trim(($person->street ?? '').' '.($person->house_number ?? '')), trim(($person->postal_code ?? '').' '.($person->city ?? ''))])->filter()->implode(', ');
    $pending = $user?->status === \App\Modules\Access\Enums\UserStatus::PendingVerification;
@endphp

<x-layouts.app :title="$name" width="wide">
    <x-vdbs.layout.page-header :title="$name" :breadcrumb="$crumbs" eyebrow="Personenverwaltung">
        @if ($user)
            <x-slot:meta><x-vdbs.data.badge :tone="$user->status->tone()">Konto: {{ $user->status->label() }}</x-vdbs.data.badge></x-slot:meta>
        @endif

        @if ($canManage)
            <x-slot:actions>
                <x-vdbs.actions.button variant="secondary" :href="route('admin.people.edit', $person)" icon="pencil">Bearbeiten</x-vdbs.actions.button>
                @unless ($user)
                    <x-vdbs.actions.button variant="danger" shape="outline" icon="trash-2" data-vdbs-dialog-open="delete-person">Löschen</x-vdbs.actions.button>
                @endunless
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="2" align="start">
        <section aria-labelledby="person-heading">
            <h2 id="person-heading" style="font-size:var(--font-size-h3)">Stammdaten</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Name">{{ $name }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Geburtsdatum">{{ $person->birth_date?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="E-Mail-Adresse">{{ $person->email }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Telefon">{{ $person->phone }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Anschrift">{{ $address }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Land">{{ $person->country_code }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Angelegt am">{{ $person->created_at?->format('d.m.Y') }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>

        <section aria-labelledby="account-heading">
            <h2 id="account-heading" style="font-size:var(--font-size-h3)">Benutzerkonto</h2>
            @if ($user)
                <x-vdbs.data.detail-list layout="inline">
                    <x-vdbs.data.detail-item label="Status">{{ $user->status->label() }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Letzte Anmeldung">{{ $user->last_login_at?->format('d.m.Y, H:i') }}</x-vdbs.data.detail-item>
                </x-vdbs.data.detail-list>
                @if ($canViewUsers)
                    <p><x-vdbs.actions.button variant="secondary" :href="route('admin.users.show', $user)" icon="user">Zum Konto</x-vdbs.actions.button></p>
                @endif
            @else
                <p>Diese Person hat noch kein Konto und kann sich nicht anmelden.</p>
                @if ($canManage && $canManageUsers)
                    <x-vdbs.actions.button icon="send" data-vdbs-dialog-open="create-account">Konto anlegen und einladen</x-vdbs.actions.button>
                @endif
            @endif
        </section>
    </x-vdbs.layout.grid>

    @if ($history !== null)
        <x-admin.history :entries="$history" :href="route('admin.audit.index', ['subject' => 'person:'.$person->id])" />
    @endif

    @if ($canManage && ! $user)
        <x-vdbs.overlays.confirm-dialog id="delete-person" title="Person löschen?" variant="danger" :action="route('admin.people.destroy', $person)" method="delete" confirm-label="Person löschen">
            <p>Die Angaben von <strong>{{ $name }}</strong> werden dauerhaft gelöscht. Das lässt sich nicht rückgängig machen.</p>
        </x-vdbs.overlays.confirm-dialog>

        @if ($canManageUsers)
            <x-vdbs.overlays.confirm-dialog id="create-account" title="Konto anlegen?" :action="route('admin.people.account', $person)" confirm-label="Konto anlegen und einladen">
                <p>An <strong>{{ $person->email }}</strong> geht eine Einladung. Darüber legt {{ $name }} das Passwort fest. Der Link ist sieben Tage gültig.</p>
            </x-vdbs.overlays.confirm-dialog>
        @endif
    @endif
</x-layouts.app>
