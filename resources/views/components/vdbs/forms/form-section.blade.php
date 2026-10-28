@props(['title', 'description' => null, 'level' => 2])

{{-- Abschnitt eines langen Formulars (Paket "forms"): Überschrift, Beschreibung, Felder. --}}

@php
    $tag = 'h'.max(2, min(4, (int) $level));
    $headingId = 'section-'.\Illuminate\Support\Str::slug($title);
@endphp

<section {{ $attributes->class('vdbs-form-section') }} aria-labelledby="{{ $headingId }}">
    <header class="vdbs-form-section__header">
        <{{ $tag }} class="vdbs-form-section__title" id="{{ $headingId }}">{{ $title }}</{{ $tag }}>
        @if ($description) <p class="vdbs-form-section__description">{{ $description }}</p> @endif
    </header>
    <div class="vdbs-form-section__body">{{ $slot }}</div>
</section>
