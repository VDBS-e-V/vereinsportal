@php
    $title = $event?->label() ?? $entry->event;
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Verwaltung'], ['label' => 'Protokoll', 'href' => route('admin.audit.index')], ['label' => $title]];
@endphp

<x-layouts.app :title="$title" width="medium">
    <x-vdbs.layout.page-header :title="$title" :breadcrumb="$crumbs" eyebrow="Protokolleintrag">
        <x-slot:meta><x-vdbs.data.badge :tone="$event?->tone() ?? 'neutral'">{{ $event ? \App\Modules\Audit\Enums\AuditEvent::categories()[$event->category()] : 'Unbekannt' }}</x-vdbs.data.badge></x-slot:meta>
    </x-vdbs.layout.page-header>

    <x-vdbs.data.detail-list layout="inline">
        <x-vdbs.data.detail-item label="Zeitpunkt">{{ $entry->occurred_at->format('d.m.Y, H:i:s') }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Ereignis">{{ $title }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Akteur">
            @if ($entry->actor_label)
                @if ($actorUrl) <a href="{{ $actorUrl }}">{{ $entry->actor_label }}</a> @else {{ $entry->actor_label }} @endif
            @endif
        </x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Betroffen">
            @if ($entry->subject_label)
                @if ($subjectUrl) <a href="{{ $subjectUrl }}">{{ $entry->subject_label }}</a> @else {{ $entry->subject_label }} @endif
            @endif
        </x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="IP-Adresse">{{ $entry->ip_address }}</x-vdbs.data.detail-item>
        @foreach ($context as $key => $value)
            <x-vdbs.data.detail-item :label="$key">{{ $value }}</x-vdbs.data.detail-item>
        @endforeach
    </x-vdbs.data.detail-list>

    <p style="margin-top:var(--space-6)"><x-vdbs.actions.button variant="tertiary" :href="route('admin.audit.index')" icon="arrow-left">Zurück zum Protokoll</x-vdbs.actions.button></p>
</x-layouts.app>
