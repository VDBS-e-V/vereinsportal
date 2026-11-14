@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'FAQ']];
    $standard = \App\Modules\ContactForms\Models\ContactForm::standard();
@endphp

<x-layouts.app title="Häufig gestellte Fragen" width="medium">
    <x-vdbs.layout.page-header title="Häufig gestellte Fragen" lead="Antworten rund um das Vereinsportal, nach Themen geordnet. Finden Sie Ihre Frage nicht, schreiben Sie uns." :breadcrumb="$crumbs" />

    <x-vdbs.forms.search name="q" label="Fragen durchsuchen" :action="route('portal.faq')" placeholder="Wonach suchen Sie? z. B. Passwort, Mitgliedschaft, Honorar" :value="$query" style="margin-bottom:var(--space-6)" />

    @if ($groups->isEmpty())
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

        @if ($groups->count() > 1)
            <x-vdbs.data.chip-list label="Themen der Fragen" style="margin-bottom:var(--space-6)">
                @foreach ($groups as $group)
                    <x-vdbs.data.chip :label="$group['area']?->title ?? 'Weitere Fragen'" :href="'#bereich-'.($group['area']?->id ?? 'weitere')" />
                @endforeach
            </x-vdbs.data.chip-list>
        @endif

        @foreach ($groups as $group)
            @php $areaId = 'bereich-'.($group['area']?->id ?? 'weitere'); @endphp
            <section id="{{ $areaId }}" aria-labelledby="{{ $areaId }}-title" style="margin-top:var(--space-7, var(--space-6))">
                <h2 id="{{ $areaId }}-title" style="font-size:var(--font-size-h2)">{{ $group['area']?->title ?? 'Weitere Fragen' }}</h2>
                @if (filled($group['area']?->description))<p>{{ $group['area']->description }}</p>@endif

                @foreach ($group['topics'] as $topic)
                    <h3 id="thema-{{ $topic->id }}" style="font-size:var(--font-size-h3);margin-top:var(--space-5)">{{ $topic->title }}</h3>
                    <x-vdbs.disclosure.accordion>
                        @foreach ($topic->faqs as $faq)
                            <x-vdbs.disclosure.accordion-item :title="$faq->question" :level="4">
                                {{ \App\Modules\ContactForms\Support\FaqText::html($faq->answer) }}
                            </x-vdbs.disclosure.accordion-item>
                        @endforeach
                    </x-vdbs.disclosure.accordion>
                @endforeach
            </section>
        @endforeach
    @endif

    <div style="margin-top:var(--space-8)">
        <p><strong>Ihre Frage ist nicht dabei?</strong></p>
        <x-vdbs.actions.button :href="$standard ? route('portal.contact.form', ['form' => $standard->slug]) : route('portal.contact')" icon="mail">Uns schreiben</x-vdbs.actions.button>
        <x-vdbs.actions.button :href="route('portal.contact')" variant="text">Thema wählen</x-vdbs.actions.button>
    </div>
</x-layouts.app>
