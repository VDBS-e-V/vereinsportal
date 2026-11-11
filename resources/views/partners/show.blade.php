@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Schulen', 'href' => route('partners.index')], ['label' => $partner->name]];
    $address = collect([trim(($partner->street ?? '').' '.($partner->house_number ?? '')), trim(($partner->postal_code ?? '').' '.($partner->city ?? ''))])->filter()->implode(', ');
@endphp

<x-layouts.app :title="$partner->name" width="wide">
    <x-vdbs.layout.page-header :title="$partner->name" :breadcrumb="$crumbs" eyebrow="Schule">
        <x-slot:meta><x-vdbs.data.badge :tone="$partner->status->tone()">{{ $partner->status->label() }}</x-vdbs.data.badge></x-slot:meta>

        @if ($canManage)
            <x-slot:actions>
                <x-vdbs.actions.button variant="secondary" :href="route('partners.edit', $partner)" icon="pencil">Bearbeiten</x-vdbs.actions.button>
                @can('mailings.send')
                    <x-vdbs.actions.button variant="secondary" :href="route('mailings.office.create', ['schule' => $partner->id])" icon="mail">Ansprechpersonen anschreiben</x-vdbs.actions.button>
                @endcan
                @can('seminars.manage')
                    <x-vdbs.actions.button variant="secondary" :href="route('seminars.create', ['schule' => $partner->id])" icon="calendar">Seminar anlegen</x-vdbs.actions.button>
                @endcan
                <x-vdbs.actions.button variant="danger" icon="trash-2" data-vdbs-dialog-open="delete-partner">Löschen</x-vdbs.actions.button>
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="2" align="start">
        <section aria-labelledby="school-heading">
            <h2 id="school-heading" style="font-size:var(--font-size-h3)">Schule</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Name">{{ $partner->name }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Träger oder offizieller Name">{{ $partner->legal_name }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Schulart">{{ $schoolType }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Bundesland">{{ $federalState }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Interne Kennung">{{ $internalId }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Schulnummer">{{ $schoolNumber }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Schülerzahl">{{ $studentCount }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Anschrift">@if ($address !== '')<x-vdbs.data.copy-text :text="$address" />@endif</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="E-Mail">@if ($partner->email)<a href="mailto:{{ $partner->email }}">{{ $partner->email }}</a>@endif</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Telefon">@if ($partner->phone)<x-vdbs.data.phone-link :number="$partner->phone" />@endif</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Webseite">@if ($partner->website)<a href="{{ $partner->website }}" target="_blank" rel="noopener noreferrer">{{ $partner->website }}</a>@endif</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>

        <section aria-labelledby="contact-heading">
            <h2 id="contact-heading" style="font-size:var(--font-size-h3)">Ansprechpersonen</h2>
            @if ($contacts->isEmpty())
                <p>Keine Ansprechperson erfasst.</p>
            @else
                <x-vdbs.data.table caption="Ansprechpersonen" :columns="[['key' => 'name', 'label' => 'Name'], ['key' => 'role', 'label' => 'Funktion'], ['key' => 'email', 'label' => 'E-Mail'], ['key' => 'phone', 'label' => 'Telefon'], ['key' => 'account', 'label' => 'Konto']]">
                    @foreach ($contacts as $contact)
                        @php($account = $partner->accounts->firstWhere('contact_id', $contact->id))
                        <tr>
                            <td>{{ $contact->name }} @if ($contact->is_primary) <x-vdbs.data.badge tone="success">Haupt</x-vdbs.data.badge> @endif</td>
                            <td>{{ $contact->role ?? '–' }}</td>
                            <td>@if ($contact->email)<a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>@else – @endif</td>
                            <td>@if ($contact->phone)<x-vdbs.data.phone-link :number="$contact->phone" />@else – @endif</td>
                            <td>
                                @if ($account)
                                    <x-vdbs.data.badge :tone="$account->user->status === \App\Modules\Access\Enums\UserStatus::Active ? 'success' : 'warning'">{{ $account->user->status === \App\Modules\Access\Enums\UserStatus::Active ? 'Konto aktiv' : 'Eingeladen' }}</x-vdbs.data.badge>
                                @elseif ($canManage && $contact->email)
                                    <form method="post" action="{{ route('partners.contacts.account', [$partner, $contact]) }}">@csrf<x-vdbs.actions.button type="submit" size="s" variant="tertiary" icon="user-plus">Konto einladen</x-vdbs.actions.button></form>
                                @else –
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-vdbs.data.table>
            @endif

            @if ($partner->accounts->isNotEmpty() && $canManage)
                <p style="color:var(--text-muted);font-size:var(--font-size-small)">
                    Konten dieser Schule:
                    @foreach ($partner->accounts as $account)
                        {{ $account->name }} ({{ $account->user->email }})@if ($account->user->status === \App\Modules\Access\Enums\UserStatus::PendingVerification)
                            <form method="post" action="{{ route('partners.accounts.resend', [$partner, $account]) }}" style="display:inline">@csrf<x-vdbs.actions.button type="submit" size="s" variant="text">Einladung erneut senden</x-vdbs.actions.button></form>
                        @endif
                        @if ($account->user->status !== \App\Modules\Access\Enums\UserStatus::PendingVerification)
                            <x-vdbs.data.badge :tone="$account->user->status === \App\Modules\Access\Enums\UserStatus::Disabled ? 'error' : 'success'">{{ $account->user->status === \App\Modules\Access\Enums\UserStatus::Disabled ? 'gesperrt' : 'aktiv' }}</x-vdbs.data.badge>
                        @endif
                        @if ($account->user->status !== \App\Modules\Access\Enums\UserStatus::PendingVerification)<form method="post" action="{{ route('partners.accounts.toggle', [$partner, $account]) }}" style="display:inline">@csrf<x-vdbs.actions.button type="submit" size="s" variant="text">{{ $account->user->status === \App\Modules\Access\Enums\UserStatus::Disabled ? 'Entsperren' : 'Sperren' }}</x-vdbs.actions.button></form>@endif{{ ! $loop->last ? ';' : '' }}
                    @endforeach
                </p>
            @endif

            <h2 style="margin-top:var(--space-6);font-size:var(--font-size-h3)">Notizen</h2>
            @if (filled($partner->notes))
                <x-vdbs.content.prose>{!! rich_text($partner->notes) !!}</x-vdbs.content.prose>
            @else
                <p>Keine Notizen.</p>
            @endif

            <p style="color:var(--text-muted);font-size:var(--font-size-small)">
                Angelegt am {{ $partner->created_at?->format('d.m.Y') }}@if ($partner->createdBy) von {{ $partner->createdBy->person?->fullName() ?: $partner->createdBy->email }}@endif.
                @if ($partner->updated_at && $partner->updated_at->ne($partner->created_at))
                    Zuletzt geändert am {{ $partner->updated_at->format('d.m.Y') }}@if ($partner->updatedBy) von {{ $partner->updatedBy->person?->fullName() ?: $partner->updatedBy->email }}@endif.
                @endif
            </p>
        </section>
    </x-vdbs.layout.grid>

    @if ($history !== null)
        <x-admin.history :entries="$history" :href="route('admin.audit.index', ['subject' => 'partner:'.$partner->id])" />
    @endif

    @if ($canManage)
        <x-vdbs.overlays.confirm-dialog id="delete-partner" title="Schule löschen?" variant="danger" :action="route('partners.destroy', $partner)" method="delete" confirm-label="Schule löschen">
            <p>Die Angaben zu <strong>{{ $partner->name }}</strong> werden dauerhaft gelöscht. Wenn die Schule nur nicht mehr aktiv ist, setzen Sie stattdessen den Status auf „Pausiert“ oder „Vergangen“.</p>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-layouts.app>
