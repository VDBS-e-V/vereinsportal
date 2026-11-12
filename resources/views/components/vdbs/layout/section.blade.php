@props([
    'title' => null,
    'lead' => null,
    'variant' => 'default',
    'align' => 'start',
    'level' => 2,
    'id' => null,
])

{{--
    Abschnitt einer Seite (Paket "layout").

    variant: default | band (graue Fläche über die volle Breite)
    align:   start | center (Überschrift und Einleitung)
    Slots:   Standard (Inhalt), actions (Buttons/Links unter dem Inhalt)
--}}

@php
    $variant = in_array($variant, ['default', 'band'], true) ? $variant : 'default';
    $align = in_array($align, ['start', 'center'], true) ? $align : 'start';
    $tag = 'h'.max(2, min(4, (int) $level));
    $id ??= $title ? 'section-'.\Illuminate\Support\Str::slug($title) : null;
@endphp

<section {{ $attributes->class(['vdbs-section', "vdbs-section--{$variant}", "vdbs-section--align-{$align}"]) }} @if ($title) aria-labelledby="{{ $id }}-title" @endif @if ($id) id="{{ $id }}" @endif>
    <div class="vdbs-section__inner">
        @if ($title || $lead)
            <header class="vdbs-section__header">
                @if ($title)
                    <{{ $tag }} class="vdbs-section__title" id="{{ $id }}-title">{{ $title }}</{{ $tag }}>
                @endif
                @if ($lead)
                    <p class="vdbs-section__lead">{{ $lead }}</p>
                @endif
            </header>
        @endif

        <div class="vdbs-section__body">{{ $slot }}</div>

        @isset($actions)
            <div class="vdbs-section__actions">{{ $actions }}</div>
        @endisset
    </div>
</section>
