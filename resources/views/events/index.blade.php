@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Veranstaltungen']];
@endphp

<x-layouts.app title="Veranstaltungen" width="wide">
    <x-vdbs.layout.page-header title="Veranstaltungen" lead="Großveranstaltungen für Schülerinnen und Schüler mehrerer Schulen. Melde dich hier an." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('events.public.certificate')" icon="file-text">Teilnahmebestätigung abrufen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($events->isEmpty())
        <x-vdbs.feedback.empty-state title="Zurzeit keine Veranstaltungen" icon="calendar">
            <p>Sobald eine Veranstaltung geplant ist, kannst du dich hier anmelden.</p>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.layout.grid :columns="3" align="start">
            @foreach ($events as $event)
                @php($first = $event->sessions->first())
                <x-vdbs.cards.teaser :title="$event->title" :href="route('events.public.show', $event->slug)" :text="\Illuminate\Support\Str::limit(strip_tags((string) $event->description), 160)" :meta="$first ? $first->starts_at->format('d.m.Y').($event->location ? ', '.$event->location : '') : $event->location" :category="$event->status->label()" />
            @endforeach
        </x-vdbs.layout.grid>
    @endif
</x-layouts.app>
