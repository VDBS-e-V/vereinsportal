@props(['paginator' => null, 'current' => 1, 'last' => 1, 'href' => '?page=:page', 'label' => 'Seitennavigation', 'edge' => 1, 'around' => 1])

{{--
    Paginierung (Paket "navigation"): Zurück, Seitenzahlen mit Auslassung, Weiter und "Seite x von y".
    paginator  ein LengthAwarePaginator (Laravel); sonst current, last und href (":page" wird ersetzt)
    edge       Seiten am Anfang und Ende, around: Seiten um die aktuelle
    Die aktuelle Seite trägt aria-current="page"; Zurück/Weiter sind auf der ersten/letzten Seite deaktiviert.
--}}

@php
    $last = $paginator ? $paginator->lastPage() : max(1, (int) $last);
    $current = $paginator ? $paginator->currentPage() : max(1, min($last, (int) $current));
    $url = fn (int $page): string => $paginator ? $paginator->url($page) : str_replace(':page', (string) $page, $href);

    $pages = collect(range(1, $last))->filter(fn (int $p): bool => $p <= $edge || $p > $last - $edge || abs($p - $current) <= $around)->values();
    $items = [];
    $previous = null;
    foreach ($pages as $page) {
        if ($previous !== null && $page - $previous > 1) {
            $items[] = null;
        }
        $items[] = $page;
        $previous = $page;
    }
@endphp

@if ($last > 1)
    <nav {{ $attributes->class('vdbs-pagination') }} aria-label="{{ $label }}">
        <ul class="vdbs-pagination__list">
            <li>
                @if ($current > 1)
                    <a class="vdbs-pagination__link vdbs-pagination__link--step" href="{{ $url($current - 1) }}" rel="prev"><x-vdbs.icon name="chevron-left" :size="18" /><span class="vdbs-pagination__step-label">Zurück</span></a>
                @else
                    <span class="vdbs-pagination__link vdbs-pagination__link--step vdbs-pagination__link--disabled" aria-disabled="true"><x-vdbs.icon name="chevron-left" :size="18" /><span class="vdbs-pagination__step-label">Zurück</span></span>
                @endif
            </li>

            @foreach ($items as $item)
                @if ($item === null)
                    <li class="vdbs-pagination__gap" aria-hidden="true">…</li>
                @else
                    <li class="vdbs-pagination__page">
                        <a class="vdbs-pagination__link" href="{{ $url($item) }}" @if ($item === $current) aria-current="page" @endif aria-label="Seite {{ $item }}">{{ $item }}</a>
                    </li>
                @endif
            @endforeach

            <li>
                @if ($current < $last)
                    <a class="vdbs-pagination__link vdbs-pagination__link--step" href="{{ $url($current + 1) }}" rel="next"><span class="vdbs-pagination__step-label">Weiter</span><x-vdbs.icon name="chevron-right" :size="18" /></a>
                @else
                    <span class="vdbs-pagination__link vdbs-pagination__link--step vdbs-pagination__link--disabled" aria-disabled="true"><span class="vdbs-pagination__step-label">Weiter</span><x-vdbs.icon name="chevron-right" :size="18" /></span>
                @endif
            </li>
        </ul>
        <p class="vdbs-pagination__summary">Seite {{ $current }} von {{ $last }}</p>
    </nav>
@endif
