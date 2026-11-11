@php($preview = $preview ?? false)
<x-layouts.app :title="$form->title" width="medium">
    <x-vdbs.layout.page-header :title="$form->title" :lead="$form->intro" />

    @if ($preview)
        <x-vdbs.feedback.alert variant="info">Vorschau: So sehen die Antwortenden das Formular. Hier kann nichts abgeschickt werden.</x-vdbs.feedback.alert>
    @elseif ($link)
        <p class="vdbs-hint">{{ $link->seminar->title }} · offen bis {{ $link->closes_at->format('d.m.Y') }}</p>
    @endif

    <x-vdbs.forms.form :action="$preview ? '#' : route('feedback.store', ['token' => $token])" style="margin-top:var(--space-6)">
        @unless ($preview)
            <x-vdbs.forms.bot-guard />
        @endunless

        @foreach ($questions as $question)
            @include('feedback._question', ['question' => $question])
        @endforeach

        <x-slot:actions>
            @unless ($preview)
                <x-vdbs.actions.button type="submit" icon="send">Antworten abschicken</x-vdbs.actions.button>
            @endunless
        </x-slot:actions>
    </x-vdbs.forms.form>

    <p class="vdbs-hint" style="margin-top:var(--space-6)">Wir speichern weder deinen Namen noch deine Adresse oder dein Gerät. Antworten werden nur zusammengefasst ausgewertet.</p>
</x-layouts.app>
