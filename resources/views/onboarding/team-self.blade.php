@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Teamende'], ['label' => 'Einarbeitung']];
    [$done, $all] = $onboarding->progress();
@endphp

<x-layouts.app title="Einarbeitung" width="medium">
    <x-vdbs.layout.page-header title="Ihre Einarbeitung" :lead="$onboarding->isComplete() ? 'Sie haben alle Schritte erledigt. Danke!' : $done.' von '.$all.' Schritten erledigt.'" :breadcrumb="$crumbs" />

    <x-vdbs.forms.error-summary />

    @foreach ($steps as $step)
        @php
            $row = $onboarding->step($step);
            $document = $currentDocuments[$step->documentKind()?->value ?? ''] ?? null;
        @endphp
        <section aria-labelledby="step-{{ $step->value }}-heading" style="margin-bottom:var(--space-8);padding-top:var(--space-4);border-top:1px solid var(--border-default)">
            <h2 id="step-{{ $step->value }}-heading" style="font-size:var(--font-size-h3)">{{ $step->label() }}
                @if ($row->isDone())<x-vdbs.data.badge tone="success">Erledigt</x-vdbs.data.badge>@else<x-vdbs.data.badge tone="warning">Offen</x-vdbs.data.badge>@endif
            </h2>

            @if ($row->isDone())
                <p>Erledigt am {{ $row->done_at->format('d.m.Y') }}.</p>
            @else
                <p>{{ $step->description() }}</p>

                @if ($step->bySelf())
                    @if ($document)
                        <p><a href="{{ route('onboarding.documents.download', $document) }}">{{ $step->label() }} lesen (PDF)</a></p>
                        <x-vdbs.forms.form :action="route('team-self.confirm', $step->value)" :summary="false">
                            <x-vdbs.forms.checkbox :id="'confirm-'.$step->value" name="confirm" label="Ich habe das Dokument gelesen und bestätige es." :required="true" />
                            <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Bestätigen</x-vdbs.actions.button></x-slot:actions>
                        </x-vdbs.forms.form>
                    @else
                        <p>Das Dokument wird von der Koordination noch bereitgestellt.</p>
                    @endif
                @else
                    <p>Dieser Schritt wird von der Koordination eingetragen.</p>
                @endif
            @endif
        </section>
    @endforeach
</x-layouts.app>
