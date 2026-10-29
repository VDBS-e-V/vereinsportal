@props(['title', 'href', 'type' => null, 'size' => null, 'text' => null, 'icon' => 'file'])

{{--
    Download-Karte (Paket "content"): Datei mit Titel, Typ und Größe. Der Titel ist der einzige Link; die ganze Karte ist klickbar.
    type  z. B. PDF, DOCX (wird auch für Screenreader vorgelesen), size z. B. "240 KB"
--}}

<article {{ $attributes->class('vdbs-download') }}>
    <span class="vdbs-download__icon" aria-hidden="true"><x-vdbs.icon :name="$icon" :size="28" /></span>
    <div class="vdbs-download__body">
        <h3 class="vdbs-download__title"><a class="vdbs-download__link" href="{{ $href }}" download>{{ $title }}<span class="vdbs-sr-only"> (Download@if ($type), {{ $type }}@endif @if ($size), {{ $size }}@endif)</span></a></h3>
        @if ($text) <p class="vdbs-download__text">{{ $text }}</p> @endif
        @if ($type || $size)
            <p class="vdbs-download__meta" aria-hidden="true">{{ collect([$type, $size])->filter()->implode(' · ') }}</p>
        @endif
    </div>
    <span class="vdbs-download__arrow" aria-hidden="true"><x-vdbs.icon name="download" :size="20" /></span>
</article>
