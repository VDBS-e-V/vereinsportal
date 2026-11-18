@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Seminare', 'href' => route('seminars.office.index')], ['label' => $seminar->number]];
@endphp

<x-layouts.app :title="$seminar->title" width="wide">
    <x-vdbs.layout.page-header :title="$seminar->title" :lead="$seminar->number.' · '.$seminar->partner->name" :breadcrumb="$crumbs" />

    <p><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></p>

    <x-vdbs.data.detail-list :columns="2">
        <x-vdbs.data.detail-item label="Schule">{{ $seminar->partner->name }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Koordination">{{ $seminar->coordination?->name }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Format">{{ $seminar->format }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Teilnehmende">{{ $seminar->participants }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Gewünschter Zeitraum">{{ $seminar->desired_period }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Schuljahr">{{ $seminar->school_year }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Ansprechperson">{{ $seminar->contact_name }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="E-Mail-Adresse">{{ $seminar->contact_email }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Anmerkungen">{{ $seminar->request_note }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>

    @if ($targets !== [])
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Nächster Schritt</h2>
        @foreach ($targets as $target)
            <x-vdbs.forms.form :action="route('seminars.office.status', $seminar)" :summary="false" style="margin-bottom:var(--space-5)">
                <input type="hidden" name="to" value="{{ $target->value }}">
                @if (\App\Modules\Seminars\Services\SeminarWorkflow::needsReason($seminar->status, $target))
                    <x-vdbs.forms.textarea :id="'comment-'.$target->value" name="comment" :label="'Begründung für „'.$target->label().'“'" :required="true" maxlength="2000" />
                @endif
                <x-slot:actions>
                    <x-vdbs.actions.button type="submit" :variant="$target->isEnded() ? 'danger' : 'primary'">{{ $target === \App\Modules\Seminars\Enums\SeminarStatus::InReview ? 'Prüfung beginnen' : ($target === \App\Modules\Seminars\Enums\SeminarStatus::Rejected ? 'Anfrage ablehnen' : 'Auf „'.$target->label().'“ setzen') }}</x-vdbs.actions.button>
                </x-slot:actions>
            </x-vdbs.forms.form>
        @endforeach
    @endif

    @include('seminars._quote-office', ['seminar' => $seminar])

    @include('seminars._release-office', ['seminar' => $seminar->load('documents')])

    <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Verlauf</h2>
    <x-vdbs.data.timeline label="Verlauf des Seminars">
        @foreach ($seminar->events as $event)
            <x-vdbs.data.timeline-item :title="$event->describe()" :time="$event->created_at?->format('d.m.Y, H:i')" :actor="$event->actor_label">
                @if (filled($event->comment)){{ $event->comment }}@endif
            </x-vdbs.data.timeline-item>
        @endforeach
    </x-vdbs.data.timeline>
</x-layouts.app>
