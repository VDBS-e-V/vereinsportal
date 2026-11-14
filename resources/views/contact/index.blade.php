@php
    $contact = config('portal.home.contact');
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Kontakt', 'href' => $area || filled($query) ? route('portal.contact') : null]];

    if ($area) {
        $crumbs[] = ['label' => $area->title];
    }

    $none = $areas->isEmpty() && $topics->isEmpty() && $results === null;
@endphp

<x-layouts.app title="Kontakt" width="medium">
    <x-vdbs.layout.page-header title="Kontakt" lead="Wählen Sie Ihr Thema. Danach fragen wir nur die Angaben ab, die dafür nötig sind." :breadcrumb="$crumbs" />

    <x-vdbs.navigation.stepper :steps="['Thema wählen', 'Ihre Daten', 'Ihre Anfrage']" :current="1" label="Ablauf der Kontaktaufnahme" />

    <x-vdbs.forms.search name="q" label="Thema suchen" :action="route('portal.contact')" placeholder="Worum geht es? z. B. Passwort, Mitgliedschaft, Schule" :value="$query" style="margin-block:var(--space-6)" />

    @if ($results !== null)
        <h2 style="font-size:var(--font-size-h3)">Themen zu „{{ $query }}“</h2>

        @if ($results->isEmpty())
            <p>Dazu gibt es kein Thema.@if ($standard) Schreiben Sie uns stattdessen direkt: <x-vdbs.actions.button :href="route('portal.contact.form', ['form' => $standard->slug])" variant="text">Mein Thema ist nicht dabei</x-vdbs.actions.button>@endif</p>
        @else
            <x-vdbs.layout.grid :columns="2" align="start">
                @foreach ($results as $topic)
                    <x-vdbs.cards.teaser variant="flat" :title="$topic->title" :href="route('portal.contact.topic', $topic)" :text="$topic->description" :category="$topic->parent?->title" />
                @endforeach
            </x-vdbs.layout.grid>
        @endif
    @elseif ($area)
        <h2 style="font-size:var(--font-size-h3)">{{ $area->title }}</h2>
        @if (filled($area->description))<p>{{ $area->description }}</p>@endif

        <x-vdbs.layout.grid :columns="2" align="start">
            @foreach ($topics as $topic)
                <x-vdbs.cards.teaser variant="flat" :title="$topic->title" :href="route('portal.contact.topic', $topic)" :text="$topic->description" />
            @endforeach
        </x-vdbs.layout.grid>

        <p style="margin-top:var(--space-6)"><x-vdbs.actions.button :href="route('portal.contact')" variant="text" icon="arrow-left">Zurück zur Themenwahl</x-vdbs.actions.button></p>
    @elseif ($none)
        <x-vdbs.feedback.alert variant="info" title="Zurzeit keine Themen">
            Die Themenwahl ist noch leer.@if ($standard) Sie können uns direkt schreiben: <a href="{{ route('portal.contact.form', ['form' => $standard->slug]) }}">Kontaktformular</a>.@endif
        </x-vdbs.feedback.alert>
    @else
        @if ($areas->isNotEmpty())
            <h2 style="font-size:var(--font-size-h3)">Worum geht es?</h2>
            <x-vdbs.layout.grid :columns="2" align="start">
                @foreach ($areas as $category)
                    <x-vdbs.cards.teaser :title="$category->title" :href="route('portal.contact', ['bereich' => $category->id])" :text="$category->description" :meta="$category->children->count().' Themen'" />
                @endforeach
            </x-vdbs.layout.grid>
        @endif

        @if ($topics->isNotEmpty())
            <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-6)">Weitere Themen</h2>
            <x-vdbs.layout.grid :columns="2" align="start">
                @foreach ($topics as $topic)
                    <x-vdbs.cards.teaser variant="flat" :title="$topic->title" :href="route('portal.contact.topic', $topic)" :text="$topic->description" />
                @endforeach
            </x-vdbs.layout.grid>
        @endif
    @endif

    @if ($standard && ! $none)
        <p style="margin-top:var(--space-6)">Ihr Thema ist nicht dabei? <a href="{{ route('portal.contact.form', ['form' => $standard->slug]) }}">Schreiben Sie uns direkt</a>.</p>
    @endif

    @if (($contact['lines'] ?? []) !== [])
        <div style="margin-top:var(--space-8)">
            <x-vdbs.cards.contact-block :title="$contact['title']" :lines="$contact['lines']" :level="2" />
        </div>
    @endif
</x-layouts.app>
