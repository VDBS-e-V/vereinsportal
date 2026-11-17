@php
    use App\Modules\Seminars\Enums\SeminarStatus;
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Teamende'], ['label' => 'Meine Leitung', 'href' => route('seminar-lead.index')], ['label' => $seminar->number ?: $seminar->title]];
    $status = $seminar->status;
    $documents = $seminar->documents->whereIn('kind', array_keys($kinds));
    $hasReport = $documents->where('kind', 'report')->isNotEmpty();
@endphp

<x-layouts.app title="Seminarleitung" width="wide">
    <x-vdbs.layout.page-header :title="$seminar->title" :lead="'Schule '.$seminar->partner->name.'. Sie leiten dieses Seminar.'" :breadcrumb="$crumbs" />

    <x-vdbs.forms.error-summary />

    <x-vdbs.data.detail-list layout="inline">
        <x-vdbs.data.detail-item label="Status"><x-vdbs.data.badge :tone="$status->tone()">{{ $status->label() }}</x-vdbs.data.badge></x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Termine">@foreach ($seminar->sessions->sortBy('starts_at') as $session){{ $session->period() }}@if ($session->location), {{ $session->location }}@endif<br>@endforeach</x-vdbs.data.detail-item>
        @if ($seminar->onboarding_at)<x-vdbs.data.detail-item label="Onboarding">{{ $seminar->onboarding_at->format('d.m.Y, H:i') }} Uhr</x-vdbs.data.detail-item>@endif
        @if ($seminar->report_due_at)<x-vdbs.data.detail-item label="Verträge und Bericht bis">{{ $seminar->report_due_at->format('d.m.Y') }}</x-vdbs.data.detail-item>@endif
    </x-vdbs.data.detail-list>

    <p><x-vdbs.actions.button :href="route('seminar-print.show', $seminar)" variant="secondary" icon="printer">Druckplaner für die Seminarmappe</x-vdbs.actions.button></p>

    @if (in_array($status, [SeminarStatus::TeamConfirmed, SeminarStatus::Prepared], true))
        <section aria-labelledby="held-heading">
            <h2 id="held-heading" style="font-size:var(--font-size-h3)">Durchführung</h2>
            <p>Nach dem letzten Termin bestätigen Sie hier, dass das Seminar stattgefunden hat. Danach laden Sie Verträge und Ergebnisbericht hoch.</p>
            <form method="post" action="{{ route('seminar-lead.held', $seminar) }}" onsubmit="return confirm('Bestätigen Sie, dass alle Termine stattgefunden haben?')">@csrf
                <x-vdbs.actions.button type="submit" icon="check">Durchführung bestätigen</x-vdbs.actions.button>
            </form>
        </section>
    @endif

    @if (in_array($status, [SeminarStatus::Held, SeminarStatus::Reported], true))
        <section aria-labelledby="upload-heading">
            <h2 id="upload-heading" style="font-size:var(--font-size-h3)">Verträge und Ergebnisbericht</h2>
            <x-vdbs.forms.form :action="route('seminar-lead.upload', $seminar)" method="post" :files="true" :summary="false">
                <x-vdbs.forms.select id="lead-kind" name="kind" label="Art" :required="true" :options="$kinds" />
                <x-vdbs.forms.input id="lead-title" name="title" label="Titel" :optional="true" maxlength="200" />
                <x-vdbs.forms.file id="lead-files" name="files[]" label="Dateien" :required="true" :multiple="true" />
                <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="upload">Hochladen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>

            @if ($documents->isEmpty())
                <p class="vdbs-hint">Noch nichts hochgeladen.</p>
            @else
                <ul>@foreach ($documents as $document)<li>{{ $document->kindLabel() }}: {{ $document->title ?: $document->original_name }} ({{ $document->created_at?->format('d.m.Y') }})</li>@endforeach</ul>
            @endif

            @if ($status === SeminarStatus::Held)
                <form method="post" action="{{ route('seminar-lead.report', $seminar) }}">@csrf
                    <x-vdbs.actions.button type="submit" icon="check" :disabled="! $hasReport">Verträge und Bericht abgeben</x-vdbs.actions.button>
                    @unless ($hasReport)<span class="vdbs-hint">Laden Sie zuerst den Ergebnisbericht hoch (Art „Ergebnisbericht“).</span>@endunless
                </form>
            @else
                <x-vdbs.feedback.alert variant="success">Verträge und Bericht sind abgegeben. Die Koordination prüft sie.</x-vdbs.feedback.alert>
            @endif
        </section>
    @endif

    @if ($status === SeminarStatus::Completed)
        <x-vdbs.feedback.alert variant="success">Das Seminar ist abgeschlossen. Vielen Dank!</x-vdbs.feedback.alert>
    @endif
</x-layouts.app>
