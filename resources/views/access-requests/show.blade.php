@php
    $name = $accessRequest->fullName();
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Zugangsanträge', 'href' => route('access-requests.index')], ['label' => $name]];
    $open = $accessRequest->status->isOpen();
    $by = fn ($u) => $u ? ($u->person?->fullName() ?: $u->email) : null;
@endphp

<x-layouts.app :title="$name" width="wide">
    <x-vdbs.layout.page-header :title="$name" :breadcrumb="$crumbs" eyebrow="Zugangsantrag">
        <x-slot:meta><x-vdbs.data.badge :tone="$accessRequest->status->tone()">{{ $accessRequest->status->label() }}</x-vdbs.data.badge></x-slot:meta>

        @if ($canManage && $open)
            <x-slot:actions>
                @if ($accessRequest->status === \App\Modules\AccessRequests\Enums\AccessRequestStatus::New)
                    <x-vdbs.forms.form :action="route('access-requests.review', $accessRequest)" :summary="false" style="display:inline">
                        <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="clock">In Prüfung nehmen</x-vdbs.actions.button></x-slot:actions>
                    </x-vdbs.forms.form>
                @endif
                <x-vdbs.actions.button icon="check" data-vdbs-dialog-open="approve">Annehmen</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="danger" shape="outline" icon="x" data-vdbs-dialog-open="reject">Ablehnen</x-vdbs.actions.button>
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    <x-vdbs.layout.grid :columns="2" align="start">
        <section aria-labelledby="person-heading">
            <h2 id="person-heading" style="font-size:var(--font-size-h3)">Antragstellende</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Name">{{ $name }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="E-Mail-Adresse"><a href="mailto:{{ $accessRequest->email }}">{{ $accessRequest->email }}</a></x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Geburtsdatum">{{ $accessRequest->birth_date->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Telefon">{{ $accessRequest->phone }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>

        <section aria-labelledby="request-heading">
            <h2 id="request-heading" style="font-size:var(--font-size-h3)">Antrag</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Eingegangen am">{{ $accessRequest->created_at?->format('d.m.Y, H:i') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Verbindung zum Verein">{{ $accessRequest->connection->label() }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Träger / Schule">{{ $accessRequest->organization }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Weitere Details"><span style="white-space:pre-line">{{ $accessRequest->details }}</span></x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Einwilligung">{{ $accessRequest->consented_at->format('d.m.Y, H:i') }}, Nutzungsbedingungen Fassung {{ $accessRequest->terms_version }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>
    </x-vdbs.layout.grid>

    <section aria-labelledby="files-heading" style="margin-top:var(--space-8)">
        <h2 id="files-heading" style="font-size:var(--font-size-h3)">Nachweise</h2>
        @if ($accessRequest->files->isEmpty())
            <p>{{ $open ? 'Es wurden keine Nachweise hochgeladen.' : 'Die Nachweise wurden mit der Entscheidung gelöscht.' }}</p>
        @else
            <ul>
                @foreach ($accessRequest->files as $file)
                    <li><a href="{{ route('access-requests.files.download', [$accessRequest, $file]) }}">{{ $file->original_name }}</a> ({{ number_format($file->size / 1024, 0, ',', '.') }} KB)</li>
                @endforeach
            </ul>
        @endif
    </section>

    @unless ($open)
        <section aria-labelledby="decision-heading" style="margin-top:var(--space-8)">
            <h2 id="decision-heading" style="font-size:var(--font-size-h3)">Entscheidung</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Entschieden am">{{ $accessRequest->decided_at?->format('d.m.Y, H:i') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Entschieden von">{{ $by($accessRequest->decidedBy) }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Notiz">{{ $accessRequest->decision_note }}</x-vdbs.data.detail-item>
                @if ($accessRequest->user)
                    <x-vdbs.data.detail-item label="Konto"><a href="{{ route('admin.users.show', $accessRequest->user) }}">{{ $accessRequest->user->email }}</a></x-vdbs.data.detail-item>
                @endif
            </x-vdbs.data.detail-list>
        </section>
    @endunless

    @if ($canManage && $open)
        <x-vdbs.overlays.confirm-dialog id="approve" title="Antrag annehmen?" :action="route('access-requests.approve', $accessRequest)" confirm-label="Annehmen">
            <p>Für <strong>{{ $name }}</strong> wird ein Konto angelegt und eine Einladung an {{ $accessRequest->email }} gesendet. Bei der Rolle Teamende beginnt zugleich die Einarbeitung.</p>
            <x-slot:fields>
                <x-vdbs.forms.select id="approve-role" name="role" label="Rolle" placeholder="Keine Rolle" :optional="true" :options="$roleOptions" :value="$accessRequest->connection->suggestedRole()?->value" />
                <x-vdbs.forms.textarea id="approve-note" name="note" label="Notiz" :optional="true" maxlength="500" :rows="2" />
            </x-slot:fields>
        </x-vdbs.overlays.confirm-dialog>

        <x-vdbs.overlays.confirm-dialog id="reject" title="Antrag ablehnen?" variant="danger" :action="route('access-requests.reject', $accessRequest)" confirm-label="Antrag ablehnen">
            <p>Der Antrag von <strong>{{ $name }}</strong> wird abgelehnt, die Nachweise werden gelöscht. Die Person erhält eine E-Mail; die Begründung ist freiwillig und steht in dieser Nachricht.</p>
            <x-slot:fields>
                <x-vdbs.forms.textarea id="reject-note" name="note" label="Begründung" :optional="true" maxlength="500" :rows="3" />
            </x-slot:fields>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-layouts.app>
