@php
    use App\Modules\Seminars\Models\SeminarFeedback;

    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Rückmeldungen']];
@endphp

<x-layouts.app title="Rückmeldungen" width="wide">
    <x-vdbs.layout.page-header title="Rückmeldungen der Schulen" lead="Bewertungen von 1 bis 5 (5 = sehr gut) und Anmerkungen zu den Seminaren." :breadcrumb="$crumbs" />

    <x-vdbs.data.toolbar label="Rückmeldungen filtern" :search="false" :reset-href="route('seminars.plan.feedback')" :count="$feedback->count().' '.($feedback->count() === 1 ? 'Rückmeldung' : 'Rückmeldungen')">
        <x-vdbs.forms.select name="coordination" label="Koordination" placeholder="Alle" :options="$coordinationOptions" :value="$coordination" />
        <x-vdbs.forms.select name="school_year" label="Schuljahr" placeholder="Alle" :options="$schoolYears" :value="$schoolYear" />
    </x-vdbs.data.toolbar>

    @if ($feedback->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Rückmeldungen" icon="message-square"><p>Sobald Schulen nach dem Bericht eine Rückmeldung geben, erscheint sie hier.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.detail-list :columns="2" layout="inline">
            @foreach (SeminarFeedback::QUESTIONS as $key => $question)
                <x-vdbs.data.detail-item :label="$question.' (Durchschnitt)'">{{ $averages[$key] !== null ? number_format($averages[$key], 1, ',', '') : '' }}</x-vdbs.data.detail-item>
            @endforeach
        </x-vdbs.data.detail-list>

        <x-vdbs.data.table caption="Rückmeldungen" :columns="[['key' => 'seminar', 'label' => 'Seminar'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'ratings', 'label' => 'Bewertungen'], ['key' => 'comment', 'label' => 'Anmerkung']]">
            @foreach ($feedback as $row)
                <tr>
                    <td><a href="{{ route('seminars.plan.show', $row->seminar) }}">{{ $row->seminar->number }} · {{ $row->seminar->title }}</a></td>
                    <td>{{ $row->seminar->partner->name }}</td>
                    <td>{{ collect(SeminarFeedback::QUESTIONS)->map(fn ($question, $key): string => $row->rating($key) ?? '–')->implode(' / ') }}</td>
                    <td>{{ $row->comment }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>
