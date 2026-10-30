@props(['items', 'label' => 'Hauptnavigation'])

{{-- $items: list of ['label' => string, 'href' => string, 'icon' => ?string, 'current' => bool, 'mark' => ?int] --}}
<nav aria-label="{{ $label }}">
    <ul class="vdbs-nav">
        @foreach ($items as $item)
            <li>
                <a class="vdbs-nav__link" href="{{ $item['href'] }}" @if ($item['current'] ?? false) aria-current="page" @endif>
                    @if (! empty($item['mark'])) <x-vdbs.data.group-mark :number="$item['mark']" :numbered="false" /> @endif
                    @if (! empty($item['icon'])) <x-vdbs.icon :name="$item['icon']" /> @endif
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
