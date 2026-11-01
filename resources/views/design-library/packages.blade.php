@php
    $tones = ['open' => 'neutral', 'in-progress' => 'warning', 'done' => 'success'];
    $total = collect($packages)->sum(fn ($package) => count($package['components']));
    $done = collect($packages)->sum(fn ($package) => collect($package['components'])->where('redesign', 'done')->count());
@endphp

<x-layouts.app title="Design-Pakete" width="wide">
    <h1>Design-Pakete</h1>
    <p>Alle Pakete des Designsystems mit ihren Komponenten, Dateien und dem Stand der gemeinsamen Umgestaltung. Fortschritt: <strong>{{ $done }} von {{ $total }}</strong> Komponenten fertig.</p>
    <p>Stand setzen: <code>php artisan design:status &lt;paket&gt; --set=&lt;komponente&gt;:in-progress</code> (open, in-progress, done). <a href="{{ route('design-library.index') }}">Zur Bibliothek</a> · <a href="{{ route('portal.design-packages') }}">Anleitung</a></p>

    @foreach ($packages as $key => $package)
        <section aria-labelledby="package-{{ $key }}" style="margin-top:var(--space-8)">
            <h2 id="package-{{ $key }}">{{ $package['title'] }} <small style="color:var(--text-muted)">({{ $key }})</small></h2>
            <p>{{ $package['description'] }}</p>

            <x-vdbs.data.table :caption="'Komponenten des Pakets '.$package['title']" :columns="[['key' => 'c', 'label' => 'Komponente'], ['key' => 's', 'label' => 'Stand'], ['key' => 'e', 'label' => 'Beispielseiten'], ['key' => 'f', 'label' => 'Dateien']]">
                @foreach ($package['components'] as $component)
                    @php $state = $component['redesign'] ?? 'open'; @endphp
                    <tr>
                        <td><code>&lt;x-vdbs.{{ $key === 'foundation' && $component['name'] === 'icon' ? 'icon' : $key.'.'.$component['name'] }}&gt;</code></td>
                        <td><x-vdbs.data.badge :tone="$tones[$state] ?? 'neutral'">{{ $states[$state] ?? $state }}</x-vdbs.data.badge></td>
                        <td>
                            @php $slugs = ($component['adopted'] ?? false) ? ($component['entries'] ?? []) : [$key.'-'.$component['name']]; @endphp
                            @forelse ($slugs as $slug)
                                <a href="{{ route('design-library.show', $slug) }}">{{ $slug }}</a>@if (! $loop->last), @endif
                            @empty
                                –
                            @endforelse
                        </td>
                        <td>
                            <small>{{ $component['blade'] ?? 'resources/views/components/vdbs/'.$key.'/'.$component['name'].'.blade.php' }}<br>
                            @if (($component['css'] ?? null) !== null && $component['css'] !== []){{ implode(', ', $component['css']) }}@elseif (! ($component['adopted'] ?? false)){{ $component['name'] }}.css @endif</small>
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>

            @if (! empty($package['shared_css']))
                <p><small>Gemeinsame CSS-Dateien des Pakets: {{ implode(', ', $package['shared_css']) }}</small></p>
            @endif
        </section>
    @endforeach
</x-layouts.app>
