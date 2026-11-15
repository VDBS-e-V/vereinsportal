@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'FAQ']];
@endphp

<x-layouts.app title="Häufig gestellte Fragen" width="medium">
    <x-vdbs.layout.page-header title="Häufig gestellte Fragen" lead="Antworten rund um das Vereinsportal, nach Themen geordnet. Finden Sie Ihre Frage nicht, schreiben Sie uns." :breadcrumb="$crumbs" />

    <x-vdbs.forms.search name="q" label="Fragen durchsuchen" :action="route('portal.faq')" placeholder="Wonach suchen Sie? z. B. Passwort, Mitgliedschaft, Honorar" :value="$query" style="margin-bottom:var(--space-6)" />

    @if ($categories->isEmpty())
        <x-vdbs.feedback.alert variant="info" :title="$query !== '' ? 'Nichts gefunden' : 'Noch keine Fragen'">
            @if ($query !== '')
                Zu „{{ $query }}“ gibt es keine Frage. <a href="{{ route('portal.faq') }}">Alle Fragen anzeigen</a> oder <a href="{{ route('portal.contact') }}">uns schreiben</a>.
            @else
                Die Fragen und Antworten werden gerade zusammengestellt. Bis dahin helfen wir Ihnen gern über das <a href="{{ route('portal.contact') }}">Kontaktformular</a>.
            @endif
        </x-vdbs.feedback.alert>
    @else
        @if ($query !== '')
            <p>{{ $total }} Treffer zu „{{ $query }}“. <a href="{{ route('portal.faq') }}">Alle Fragen anzeigen</a></p>
        @endif

        @if ($categories->count() > 1)
            <x-vdbs.data.chip-list label="Themen der Fragen" style="margin-bottom:var(--space-6)">
                @foreach ($categories as $category)
                    <x-vdbs.data.chip :label="$category->title" :href="'#kategorie-'.$category->id" />
                @endforeach
            </x-vdbs.data.chip-list>
        @endif

        @foreach ($categories as $category)
            <section id="kategorie-{{ $category->id }}" aria-labelledby="kategorie-{{ $category->id }}-title" style="margin-top:var(--space-6)">
                <h2 id="kategorie-{{ $category->id }}-title" style="font-size:var(--font-size-h2)">{{ $category->title }}</h2>
                @if ($category->audienceLabel())<p><x-vdbs.data.badge tone="info">{{ $category->audienceLabel() }}</x-vdbs.data.badge></p>@endif
                @if (filled($category->description))<p>{{ $category->description }}</p>@endif

                <x-vdbs.disclosure.accordion>
                    @foreach ($category->entries as $entry)
                        <x-vdbs.disclosure.accordion-item :title="$entry->question" :level="3">
                            <div class="vdbs-prose">{!! \App\Support\Html\RichText::render($entry->answer) !!}</div>
                        </x-vdbs.disclosure.accordion-item>
                    @endforeach
                </x-vdbs.disclosure.accordion>
            </section>
        @endforeach
    @endif

    <div style="margin-top:var(--space-8)">
        <p><strong>Ihre Frage ist nicht dabei?</strong></p>
        <x-vdbs.actions.button :href="route('portal.contact')" icon="mail">Uns schreiben</x-vdbs.actions.button>
        @auth<x-vdbs.actions.button :href="route('support.create')" variant="secondary" icon="ticket">Ticket erstellen</x-vdbs.actions.button>@endauth
    </div>
</x-layouts.app>
