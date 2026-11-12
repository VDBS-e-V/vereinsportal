@props(['items'])

{{-- $items: list of ['label' => string, 'href' => ?string]; letztes Element ist die aktuelle Seite --}}
<nav class="vdbs-breadcrumb" aria-label="Brotkrumen">
    <ol>
        @foreach ($items as $item)
            <li>
                @if (! $loop->last && ! empty($item['href']))
                    <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                @else
                    <span @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
