<x-layouts.app title="Design-Bibliothek">
    @push('vdbs-breadcrumb')
        <x-vdbs.navigation.breadcrumb :items="\App\Support\PortalNavigation::breadcrumb([['label' => 'Design-Bibliothek']], request()->route()?->getName())" />
    @endpush

    <h1>Design-Bibliothek</h1>
    <p>Tokens, Komponenten, Marken-Bausteine, Muster und Richtlinien des VDBS-Vereinsportals nach dem VDBS-Web-Styleguide.</p>
    <p><x-vdbs.actions.button variant="secondary" :href="route('design-library.packages')" icon="list">Pakete und Stand der Umgestaltung</x-vdbs.actions.button> · <a href="{{ route('portal.design-packages') }}">Anleitung für Design-Pakete</a></p>

    @foreach ($categories as $category)
        <section aria-labelledby="cat-{{ $category->value }}">
            <h2 id="cat-{{ $category->value }}">{{ $category->label() }}</h2>
            <div style="display:grid;gap:var(--space-4);grid-template-columns:repeat(auto-fill,minmax(16rem,1fr));margin-bottom:var(--space-6)">
                @foreach ($grouped->get($category->value, collect()) as $entry)
                    <x-vdbs.cards.card :title="$entry['title']">
                        <p>{{ $entry['description'] }}</p>
                        <p>
                            <x-vdbs.data.badge :tone="$entry['status']->badgeTone()">{{ $entry['status']->label() }}</x-vdbs.data.badge>
                        </p>
                        <x-vdbs.actions.button variant="text" :href="route('design-library.show', $entry['slug'])">Ansehen<span class="vdbs-sr-only">: {{ $entry['title'] }}</span></x-vdbs.actions.button>
                    </x-vdbs.cards.card>
                @endforeach
            </div>
        </section>
    @endforeach
</x-layouts.app>
