@php
    $feedbackLinks = $seminar->feedbackLinks()->withCount('responses')->orderBy('audience')->orderByDesc('id')->get();
    $linkService = app(\App\Modules\Feedback\Services\FeedbackLinks::class);
    $linkDefaults = $linkService->defaults($seminar, 'students');
@endphp

<section aria-labelledby="feedback-heading" style="margin-top:var(--space-8)">
    <h2 id="feedback-heading" style="font-size:var(--font-size-h3)">Feedback</h2>

    <x-vdbs.feedback.alert variant="info">Ein Link gilt für viele Antworten. Die Antworten sind anonym; gespeichert werden nur die Antworten und der Tag der Abgabe. Teilen Sie den Link im Unterricht oder drucken Sie den Aushang.</x-vdbs.feedback.alert>

    @error('feedback') <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert> @enderror

    @if ($feedbackLinks->isEmpty())
        <p>Noch keine Feedbacklinks angelegt.</p>
    @else
        <form id="feedback-close-form" method="post" action="{{ route('seminars.feedback.close', $seminar) }}">@csrf</form>
        <x-vdbs.data.table caption="Feedbacklinks des Seminars" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'feedback-close-form'], ['key' => 'group', 'label' => 'Gruppe'], ['key' => 'state', 'label' => 'Stand'], ['key' => 'period', 'label' => 'Zeitraum'], ['key' => 'count', 'label' => 'Antworten'], ['key' => 'link', 'label' => 'Link']]">
            @foreach ($feedbackLinks as $feedbackLink)
                @php($state = $linkService->state($feedbackLink))
                <tr>
                    <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $feedbackLink->id }}" form="feedback-close-form" aria-label="Link für {{ $feedbackLink->audienceLabel() }} auswählen"></span></td>
                    <td>{{ $feedbackLink->audienceLabel() }}@if ($feedbackLink->label)<br><span class="vdbs-hint">{{ $feedbackLink->label }}</span>@endif</td>
                    <td><x-vdbs.data.badge :tone="$state === 'open' ? 'success' : 'neutral'">{{ \App\Modules\Feedback\Services\FeedbackLinks::STATES[$state] }}</x-vdbs.data.badge></td>
                    <td>{{ $feedbackLink->opens_at->format('d.m.Y') }} – {{ $feedbackLink->closes_at->format('d.m.Y') }}</td>
                    <td>{{ $feedbackLink->responses_count }}@if ($feedbackLink->max_responses) von höchstens {{ $feedbackLink->max_responses }}@endif</td>
                    <td><a href="{{ $feedbackLink->url() }}">{{ $feedbackLink->url() }}</a><br><a href="{{ route('seminars.feedback.print', [$seminar, $feedbackLink]) }}">Aushang drucken</a></td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
        <x-vdbs.actions.button type="submit" form="feedback-close-form" variant="secondary" icon="x">Ausgewählte Links beenden</x-vdbs.actions.button>
    @endif

    <x-vdbs.forms.form :action="route('seminars.feedback.store', $seminar)" style="margin-top:var(--space-5)" :summary="false">
        <x-vdbs.forms.form-section title="Neue Feedbacklinks">
            <x-vdbs.forms.checkbox-group name="audiences" legend="Für wen?" :required="true" :options="\App\Modules\Feedback\Models\FeedbackForm::AUDIENCES" :value="['students']" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="opens_at" type="date" label="Offen ab" :optional="true" :value="$linkDefaults['opens_at']->format('Y-m-d')" hint="Standard: erster Termin" />
                <x-vdbs.forms.input name="closes_at" type="date" label="Offen bis" :optional="true" :value="$linkDefaults['closes_at']->format('Y-m-d')" hint="Standard: 14 Tage nach dem letzten Termin" />
                <x-vdbs.forms.input name="max_responses" type="number" label="Höchstens so viele Antworten" :optional="true" min="1" hint="Leer lassen: bei Klassen das Anderthalbfache der erwarteten Teilnehmenden." />
                <x-vdbs.forms.input name="label" label="Bezeichnung" :optional="true" maxlength="100" hint="z. B. Klasse 8b" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>
        <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="plus">Links anlegen</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
</section>
