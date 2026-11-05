@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Feedback-Auswertung']];
    $avgText = fn ($v) => number_format((float) $v, 2, ',', '');
@endphp
<x-layouts.app title="Feedback-Auswertung" width="wide">
    <x-vdbs.layout.page-header title="Feedback-Auswertung" lead="Vergleich über mehrere Seminare. Gezeigt werden nur Links mit genug Antworten; Einzelantworten und Texte sehen Sie je Seminar." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('feedback-eval.export', request()->query())" icon="download">CSV herunterladen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.data.toolbar label="Auswertung filtern" :count="count($report['rows'])" :active="$tags" :reset-href="route('feedback-eval.index')" :search="false">
        <x-vdbs.forms.multi-select name="jahr" label="Jahr" placeholder="Alle Jahre" :options="$yearOptions" :value="$filters['years']" />
        <x-vdbs.forms.multi-select name="leistung" label="Leistung" placeholder="Alle Leistungen" :options="$typeOptions" :value="$filters['types']" />
        <x-vdbs.forms.multi-select name="schule" label="Schule" :searchable="true" placeholder="Alle Schulen" search-label="Schulen filtern" :options="$schoolOptions" :value="$filters['schools']" />
        <x-vdbs.forms.multi-select name="gruppe" label="Gruppe" placeholder="Alle Gruppen" :options="$audienceOptions" :value="$filters['audiences']" />
    </x-vdbs.data.toolbar>

    @if ($report['averages'] !== [])
        <x-vdbs.layout.grid :columns="3">
            @foreach ($report['averages'] as $audience => $avg)
                <x-vdbs.data.stat :label="\App\Modules\Feedback\Models\FeedbackForm::AUDIENCES[$audience] ?? $audience" value="{{ $avgText($avg['avg']) }}" icon="star" />
            @endforeach
        </x-vdbs.layout.grid>
    @endif

    @if ($report['hidden'] > 0)
        <p class="vdbs-hint">{{ $report['hidden'] }} Link(s) sind nicht aufgeführt, weil noch zu wenige Antworten vorliegen.</p>
    @endif

    @if ($report['rows'] === [])
        <x-vdbs.feedback.empty-state title="Keine Auswertung" icon="info">
            <p>Sobald Links genug Antworten haben, erscheinen sie hier. Zu den gewählten Filtern gibt es nichts.</p>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Rückmeldungen je Seminar" :columns="[['key' => 'number', 'label' => 'Nr.'], ['key' => 'title', 'label' => 'Seminar'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'year', 'label' => 'Jahr'], ['key' => 'group', 'label' => 'Gruppe'], ['key' => 'count', 'label' => 'Antworten'], ['key' => 'rate', 'label' => 'Rücklauf'], ['key' => 'avg', 'label' => 'Gesamteindruck']]">
            @foreach ($report['rows'] as $row)
                <tr>
                    <td style="white-space:nowrap">{{ $row['number'] }}</td>
                    <td><a href="{{ route('feedback-eval.seminar', $row['id']) }}">{{ $row['title'] }}</a></td>
                    <td>{{ $row['school'] }}</td>
                    <td>{{ $row['year'] }}</td>
                    <td>{{ $row['audience_label'] }}@if ($row['archived']) <x-vdbs.data.badge tone="neutral">Archiv</x-vdbs.data.badge>@endif</td>
                    <td>{{ $row['count'] }}</td>
                    <td>{{ $row['rate'] !== null ? number_format($row['rate'], 0).' %' : '–' }}</td>
                    <td>{{ $row['avg'] !== null ? $avgText($row['avg']) : '–' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>
