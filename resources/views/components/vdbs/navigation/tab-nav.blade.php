@props(['items', 'label'])

{{--
    Tabs als Links (Paket "navigation"): jeder Tab ist eine eigene Seite, z. B. Konto > Profil | Sicherheit | Sitzungen.
    $items: Liste aus ['label' => string, 'href' => string, 'current' => bool, 'icon' => ?string, 'badge' => ?string]
    label:  Beschriftung der Navigation für Screenreader (Pflicht)
    Für Tabs mit Inhalt auf derselben Seite: <x-vdbs.navigation.tabs>.
--}}

<nav {{ $attributes->class('vdbs-tab-nav') }} aria-label="{{ $label }}">
    <ul class="vdbs-tab-nav__list">
        @foreach ($items as $item)
            <li>
                <a class="vdbs-tab-nav__link" href="{{ $item['href'] }}" @if ($item['current'] ?? false) aria-current="page" @endif>
                    @if (! empty($item['icon'])) <x-vdbs.icon :name="$item['icon']" :size="18" /> @endif
                    {{ $item['label'] }}
                    @if (! empty($item['badge'])) <span class="vdbs-tab-nav__badge">{{ $item['badge'] }}</span> @endif
                </a>
            </li>
        @endforeach
    </ul>
</nav>
