@props([
    'title' => null,
    'reverse' => false,
    'level' => 2,
    'id' => null,
])

{{--
    Text neben Bild (Paket "layout"), Vorbild: FU "Studium/Forschung", "Zugang zum Portal".
    Slots: Standard (Text), actions (Buttons/Links), media (Bild oder <x-vdbs.media.figure>)
    reverse: Bild links statt rechts.
--}}

@php
    $tag = 'h'.max(2, min(4, (int) $level));
    $id ??= $title ? 'split-'.\Illuminate\Support\Str::slug($title) : null;
@endphp

<div {{ $attributes->class(['vdbs-split', 'vdbs-split--reverse' => $reverse]) }} @if ($title) role="group" aria-labelledby="{{ $id }}-title" @endif>
    <div class="vdbs-split__text">
        @if ($title)
            <{{ $tag }} class="vdbs-split__title" id="{{ $id }}-title">{{ $title }}</{{ $tag }}>
        @endif

        <div class="vdbs-split__body">{{ $slot }}</div>

        @isset($actions)
            <div class="vdbs-split__actions">{{ $actions }}</div>
        @endisset
    </div>

    @isset($media)
        <div class="vdbs-split__media">{{ $media }}</div>
    @endisset
</div>
