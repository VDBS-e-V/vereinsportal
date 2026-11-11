@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Seminare', 'href' => route('seminars.index')], ['label' => $seminar->title, 'href' => route('seminars.show', $seminar)], ['label' => 'Feedback']];
@endphp
<x-layouts.app title="Feedback zum Seminar" width="wide">
    <x-vdbs.layout.page-header title="Feedback zum Seminar" :lead="$seminar->number.' '.$seminar->title" :breadcrumb="$crumbs" />

    @if ($results === [])
        <x-vdbs.feedback.empty-state title="Noch kein Feedbacklink" icon="info">
            <p>Legen Sie auf der Seminarseite Links für Klassen, Lehrkräfte oder Teamende an.</p>
        </x-vdbs.feedback.empty-state>
    @else
        @can('feedback.manage')
            <form id="anonymize-form" method="post" action="{{ route('feedback-eval.anonymize', $seminar) }}" onsubmit="return confirm('Ausgewählte Links jetzt anonymisieren? Einzelantworten, Freitexte und Namen werden gelöscht. Das lässt sich nicht rückgängig machen.')">@csrf</form>
        @endcan

        @foreach ($results as $result)
            @can('feedback.manage')
                @unless ($result['link']->anonymized_at)
                    <span class="vdbs-choice vdbs-choice--checkbox" style="margin-top:var(--space-4)"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $result['link']->id }}" form="anonymize-form" id="anon-{{ $result['link']->id }}"><label for="anon-{{ $result['link']->id }}">Zum Anonymisieren auswählen</label></span>
                @endunless
            @endcan
            @include('feedback._result', ['result' => $result])
        @endforeach

        @can('feedback.manage')
            <x-vdbs.actions.button type="submit" form="anonymize-form" variant="secondary" icon="trash-2">Ausgewählte Links anonymisieren</x-vdbs.actions.button>
        @endcan
    @endif
</x-layouts.app>
