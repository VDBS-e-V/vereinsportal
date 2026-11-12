@props(['codes', 'label' => 'Codes'])

{{--
    Liste von Einmalcodes (Paket "data"), z. B. Wiederherstellungscodes: Festbreitenschrift, zwei Spalten, gut druckbar.
    codes  Liste von Texten
--}}

<ul {{ $attributes->class('vdbs-code-list') }} aria-label="{{ $label }}">
    @foreach ($codes as $code)
        <li class="vdbs-code-list__item"><code>{{ $code }}</code></li>
    @endforeach
</ul>
