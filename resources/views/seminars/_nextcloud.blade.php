@php
    $nextcloud = app(\App\Modules\Seminars\Nextcloud\SeminarNextcloud::class);
    $enabled = $nextcloud->enabled();
    $from = [\App\Modules\Seminars\Enums\SeminarStatus::TeamConfirmed, \App\Modules\Seminars\Enums\SeminarStatus::Prepared, \App\Modules\Seminars\Enums\SeminarStatus::Held, \App\Modules\Seminars\Enums\SeminarStatus::Reported, \App\Modules\Seminars\Enums\SeminarStatus::Completed];
@endphp

@can('seminars.manage')
    @if (in_array($seminar->status, $from, true))
        <section aria-labelledby="nextcloud-heading" style="margin-top:var(--space-8)">
            <h2 id="nextcloud-heading" style="font-size:var(--font-size-h3)">Nextcloud-Ordner</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Anbindung">{{ $enabled ? 'eingerichtet' : 'nicht eingerichtet' }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Ordner">{{ $seminar->nextcloud_folder_path ?? ($seminar->nextcloud_manual ? 'von Hand angelegt' : 'noch nicht angelegt') }}</x-vdbs.data.detail-item>
                @if ($seminar->nextcloud_status === 'failed')<x-vdbs.data.detail-item label="Fehler">{{ $seminar->nextcloud_error }}</x-vdbs.data.detail-item>@endif
                @if ($seminar->nextcloud_synced_at)<x-vdbs.data.detail-item label="Zuletzt abgeglichen">{{ $seminar->nextcloud_synced_at->format('d.m.Y H:i') }}</x-vdbs.data.detail-item>@endif
            </x-vdbs.data.detail-list>
            <div class="vdbs-task-filter">
                @if ($enabled)
                    <form method="post" action="{{ route('seminars.nextcloud.sync', $seminar) }}">@csrf<x-vdbs.actions.button type="submit" variant="secondary" icon="folder">{{ $seminar->nextcloud_folder_path ? 'Zugriff abgleichen' : 'Ordner anlegen' }}</x-vdbs.actions.button></form>
                    @if ($seminar->nextcloud_folder_path && ! $seminar->results_share_url)
                        <form method="post" action="{{ route('seminars.nextcloud.link', $seminar) }}">@csrf<x-vdbs.actions.button type="submit" variant="secondary" icon="link">Ergebnislink erzeugen</x-vdbs.actions.button></form>
                    @endif
                @elseif (! $seminar->nextcloud_manual)
                    <form method="post" action="{{ route('seminars.nextcloud.manual', $seminar) }}">@csrf<x-vdbs.actions.button type="submit" variant="secondary" icon="check">Ordner wird von Hand angelegt</x-vdbs.actions.button></form>
                @endif
            </div>
        </section>
    @endif
@endcan
