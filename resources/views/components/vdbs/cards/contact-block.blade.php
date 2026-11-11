@props([
    'title' => 'Kontakt',
    'text' => null,
    'lines' => [],
    'level' => 2,
    'surface' => false,
])

{{--
    Kontaktblock (Paket "cards"), Vorbild: Ihr Entwurf "Kontakt".
    $lines: Liste von ['label' => 'Support', 'value' => 'support@…', 'href' => 'mailto:support@…'] (href optional)
    Slots: media (Bild), actions (Button)
    surface: graue Fläche mit Innenabstand um den ganzen Block
--}}

@php
    $tag = 'h'.max(2, min(4, (int) $level));
@endphp

<section {{ $attributes->class(['vdbs-contact-block', 'vdbs-contact-block--surface' => $surface]) }} aria-labelledby="contact-block-title">
    @isset($media)
        <div class="vdbs-contact-block__media">{{ $media }}</div>
    @endisset

    <div class="vdbs-contact-block__content">
        <{{ $tag }} class="vdbs-contact-block__title" id="contact-block-title">{{ $title }}</{{ $tag }}>

        @if ($text)
            <p class="vdbs-contact-block__text">{{ $text }}</p>
        @endif

        @if (count($lines))
            <dl class="vdbs-contact-block__lines">
                @foreach ($lines as $line)
                    <div class="vdbs-contact-block__line">
                        <dt class="vdbs-contact-block__label">{{ $line['label'] }}</dt>
                        <dd class="vdbs-contact-block__value">
                            @if (! empty($line['href']))
                                <a href="{{ $line['href'] }}">{{ $line['value'] }}</a>
                            @else
                                {{ $line['value'] }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif

        @isset($actions)
            <div class="vdbs-contact-block__actions">{{ $actions }}</div>
        @endisset
    </div>
</section>
