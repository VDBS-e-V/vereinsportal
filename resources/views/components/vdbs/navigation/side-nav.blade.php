@props(['sections', 'label'])

{{--
    Seitennavigation (Paket "navigation") für Konto und Verwaltung; steht z. B. in <x-vdbs.layout.aside-layout variant="sidebar">.
    sections  Liste aus ['title' => ?string, 'items' => [['label', 'href', 'icon' => ?string, 'current' => bool, 'badge' => ?string]]]
              (eine flache Liste von Einträgen wird als ein Abschnitt ohne Titel behandelt)
    label     Beschriftung der Navigation für Screenreader (Pflicht)
--}}

@php
    $sections = isset($sections[0]['label']) ? [['title' => null, 'items' => $sections]] : $sections;
@endphp

<nav {{ $attributes->class('vdbs-side-nav') }} aria-label="{{ $label }}">
    @foreach ($sections as $section)
        <div class="vdbs-side-nav__section">
            @if (! empty($section['title']))
                <p class="vdbs-side-nav__title">{{ $section['title'] }}</p>
            @endif
            <ul class="vdbs-side-nav__list">
                @foreach ($section['items'] as $item)
                    <li>
                        <a class="vdbs-side-nav__link" href="{{ $item['href'] }}" @if ($item['current'] ?? false) aria-current="page" @endif>
                            @if (! empty($item['icon'])) <x-vdbs.icon :name="$item['icon']" :size="18" /> @endif
                            <span class="vdbs-side-nav__label">{{ $item['label'] }}</span>
                            @if (! empty($item['badge'])) <span class="vdbs-side-nav__badge">{{ $item['badge'] }}</span> @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
