@php
    $user = $onboarding->user;
    $name = $user->person?->fullName() ?: $user->email;
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Teamende', 'href' => route('team-onboarding.index')], ['label' => $name]];
    [$done, $all] = $onboarding->progress();
    $by = fn ($u) => $u ? ($u->person?->fullName() ?: $u->email) : null;
@endphp

<x-layouts.app :title="$name" width="wide">
    <x-vdbs.layout.page-header :title="$name" :lead="'Einarbeitung seit '.$onboarding->started_at->format('d.m.Y').': '.$done.' von '.$all.' Schritten erledigt.'" :breadcrumb="$crumbs" eyebrow="Teamende:r">
        <x-slot:meta>
            @if ($onboarding->isComplete())<x-vdbs.data.badge tone="success">Abgeschlossen am {{ $onboarding->completed_at->format('d.m.Y') }}</x-vdbs.data.badge>@else<x-vdbs.data.badge tone="warning">In Einarbeitung</x-vdbs.data.badge>@endif
        </x-slot:meta>
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    @foreach ($steps as $step)
        @php
            $row = $onboarding->step($step);
            $document = $currentDocuments[$step->documentKind()?->value ?? ''] ?? null;
        @endphp
        <section aria-labelledby="step-{{ $step->value }}-heading" style="margin-bottom:var(--space-8);padding-top:var(--space-4);border-top:1px solid var(--border-default)">
            <h2 id="step-{{ $step->value }}-heading" style="font-size:var(--font-size-h3)">{{ $step->label() }}
                @if ($row->isDone())<x-vdbs.data.badge tone="success">Erledigt</x-vdbs.data.badge>@else<x-vdbs.data.badge>Offen</x-vdbs.data.badge>@endif
            </h2>
            <p>{{ $step->bySelf() ? 'Bestätigen die Teamenden selbst.' : 'Trägt die Koordination ein.' }}</p>

            @if ($row->isDone())
                <x-vdbs.data.detail-list layout="inline">
                    <x-vdbs.data.detail-item label="Erledigt am">{{ $row->done_at->format('d.m.Y, H:i') }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Durch">{{ $row->by_self ? 'die Person selbst' : $by($row->doneBy) }}</x-vdbs.data.detail-item>
                    @if ($step->dateLabel())
                        <x-vdbs.data.detail-item :label="$step->dateLabel()">{{ isset($row->data['datum']) ? \Illuminate\Support\Carbon::parse($row->data['datum'])->format('d.m.Y') : null }}</x-vdbs.data.detail-item>
                    @endif
                    @if ($row->document)
                        <x-vdbs.data.detail-item label="Bestätigte Fassung">{{ $row->document->original_name }} vom {{ $row->document->uploaded_at->format('d.m.Y') }}</x-vdbs.data.detail-item>
                    @endif
                    <x-vdbs.data.detail-item label="Notiz">{{ $row->note }}</x-vdbs.data.detail-item>
                </x-vdbs.data.detail-list>

                @if ($canManage)
                    <x-vdbs.forms.form :action="route('team-onboarding.reopen', [$user, $step->value])" method="delete" :summary="false" style="margin-top:var(--space-3)">
                        <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" size="s" icon="refresh-cw">Wieder öffnen</x-vdbs.actions.button></x-slot:actions>
                    </x-vdbs.forms.form>
                @endif
            @elseif ($canManage)
                <x-vdbs.forms.form :action="route('team-onboarding.mark', [$user, $step->value])" :summary="false">
                    <x-vdbs.forms.form-grid :columns="2">
                        @if ($step->dateLabel())
                            <x-vdbs.forms.input :id="'date-'.$step->value" name="date" :label="$step->dateLabel()" type="date" :required="true" :max="now()->toDateString()" />
                        @endif
                        <x-vdbs.forms.input :id="'note-'.$step->value" name="note" label="Notiz" :optional="true" maxlength="500" />
                    </x-vdbs.forms.form-grid>
                    <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">{{ $step->bySelf() ? 'Stellvertretend als erledigt eintragen' : 'Als erledigt eintragen' }}</x-vdbs.actions.button></x-slot:actions>
                </x-vdbs.forms.form>
            @endif
        </section>
    @endforeach
</x-layouts.app>
