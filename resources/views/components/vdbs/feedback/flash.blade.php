@props([])

{{--
    Zeigt die Flash-Meldungen der Sitzung als Toasts (Paket "feedback"), z. B. nach
    return back()->with('success', 'Gespeichert.'). Gelesen werden die Schlüssel
    success, status (wie success), info, warning und error. Ohne Meldung erscheint nichts.
    Steht einmal im Seitenrahmen (layouts.app).
--}}

@php
    $messages = collect([
        'error' => session('error'),
        'warning' => session('warning'),
        'success' => session('success') ?? session('status'),
        'info' => session('info'),
    ])->filter(fn ($text): bool => is_string($text) && $text !== '');
@endphp

@if ($messages->isNotEmpty())
    <x-vdbs.feedback.toast-region>
        @foreach ($messages as $variant => $text)
            <x-vdbs.feedback.toast :variant="$variant">{{ $text }}</x-vdbs.feedback.toast>
        @endforeach
    </x-vdbs.feedback.toast-region>
@endif
