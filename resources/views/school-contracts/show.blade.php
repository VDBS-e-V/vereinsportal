@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Seminare', 'href' => route('seminar-office.index')], ['label' => $seminar->number ?: $seminar->title]];
@endphp

<x-layouts.app title="Vertrag mit der Schule" width="wide">
    <x-vdbs.layout.page-header :title="'Vertrag mit der Schule: '.$seminar->partner->name" :lead="'Seminar '.$seminar->number.' „'.$seminar->title.'“. Das Portal erzeugt den Vertrag aus der Vorlage; unterschrieben wird auf Papier.'" :breadcrumb="$crumbs" />

    @if ($contract === null)
        <x-vdbs.feedback.empty-state title="Noch kein Vertrag" icon="file-text">
            <p>Der Vertrag entsteht aus der aktuellen Vorlage mit Schule, Leistung, Terminen und dem Preis aus dem Kostenvoranschlag. Fehlende Angaben bleiben als Linie zum Ausfüllen von Hand.</p>
            <form method="post" action="{{ route('school-contracts.store', $seminar) }}">@csrf
                <x-vdbs.actions.button type="submit" icon="plus">Vertrag erzeugen</x-vdbs.actions.button>
            </form>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.detail-list layout="inline">
            <x-vdbs.data.detail-item label="Status"><x-vdbs.data.badge :tone="$contract->status === 'signed' ? 'success' : 'warning'">{{ $contract->statusLabel() }}</x-vdbs.data.badge></x-vdbs.data.detail-item>
            <x-vdbs.data.detail-item label="Fassung der Vorlage">{{ $contract->template_version }}</x-vdbs.data.detail-item>
            <x-vdbs.data.detail-item label="Unterschrieben am">{{ $contract->signed_on?->format('d.m.Y') ?? 'noch nicht erfasst' }}</x-vdbs.data.detail-item>
        </x-vdbs.data.detail-list>

        <div class="vdbs-task-filter">
            <x-vdbs.actions.button :href="route('school-contracts.print', [$seminar, $contract])" icon="printer" external>Drucken</x-vdbs.actions.button>
            <form method="post" action="{{ route('school-contracts.cancel', [$seminar, $contract]) }}" onsubmit="return confirm('Vertrag stornieren? Danach lässt sich ein neuer erzeugen.')">@csrf
                <x-vdbs.actions.button type="submit" variant="secondary" icon="x">Stornieren</x-vdbs.actions.button>
            </form>
        </div>

        <section aria-labelledby="sign-heading">
            <h2 id="sign-heading" style="font-size:var(--font-size-h3)">Unterschrift erfassen</h2>
            <x-vdbs.forms.form :action="route('school-contracts.sign', [$seminar, $contract])" method="post">
                <x-vdbs.forms.input id="signed-on" name="signed_on" type="date" label="Datum der Unterschrift" :required="true" :value="$contract->signed_on?->toDateString()" />
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Unterschrift erfassen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        </section>

        <section aria-labelledby="text-heading">
            <h2 id="text-heading" style="font-size:var(--font-size-h3)">Vertragstext</h2>
            <x-vdbs.content.prose>{!! $contract->body !!}</x-vdbs.content.prose>
        </section>
    @endif

    <p><a href="{{ route('seminars.show', $seminar) }}">Zum Seminar</a> · <a href="{{ route('school-contracts.template') }}">Vorlage bearbeiten</a></p>
</x-layouts.app>
