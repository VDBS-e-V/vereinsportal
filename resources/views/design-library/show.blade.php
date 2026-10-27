<x-layouts.app :title="$entry['title']">
    <x-vdbs.breadcrumb :items="[
        ['label' => 'Design-Bibliothek', 'href' => route('design-library.index')],
        ['label' => $entry['category']->label()],
        ['label' => $entry['title']],
    ]" />

    <h1>{{ $entry['title'] }}</h1>
    <p>
        <x-vdbs.badge :tone="$entry['status']->badgeTone()">{{ $entry['status']->label() }}</x-vdbs.badge>
        <x-vdbs.badge>{{ $entry['category']->label() }}</x-vdbs.badge>
        <span style="color:var(--text-muted)">Styleguide {{ $entry['styleguide'] }}</span>
    </p>
    <p>{{ $entry['description'] }}</p>

    @include($entry['view'])
</x-layouts.app>
