{{-- Brotkrumen gehören zum Kopfbereich: Leiste direkt darunter, erste Krume = Startseite des Bereichs --}}
@push('vdbs-breadcrumb')
    <x-vdbs.navigation.breadcrumb :items="\App\Support\PortalNavigation::breadcrumb([
        ['label' => 'Design-Bibliothek', 'href' => route('design-library.index')],
        ['label' => $entry['category']->label()],
        ['label' => $entry['title']],
    ], request()->route()?->getName())" />
@endpush

<h1>{{ $entry['title'] }}</h1>
<p>
    <x-vdbs.data.badge :tone="$entry['status']->badgeTone()">{{ $entry['status']->label() }}</x-vdbs.data.badge>
    <x-vdbs.data.badge>{{ $entry['category']->label() }}</x-vdbs.data.badge>
    <span style="color:var(--text-muted)">Styleguide {{ $entry['styleguide'] }}</span>
</p>
<p>{{ $entry['description'] }}</p>
