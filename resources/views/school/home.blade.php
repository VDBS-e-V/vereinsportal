@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Schulen']];
@endphp

<x-layouts.app title="Ihre Schule" width="wide">
    <x-vdbs.layout.page-header :title="$account->partner->name" lead="Ihre Seminare beim VDBS, die Kostenvoranschläge und die Verbindung zur Koordination." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('school.requests.create')" icon="plus">Seminar anfragen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @include('portal._tasks', ['area' => \App\Support\PortalAreas::Schulen])


    <x-vdbs.forms.error-summary />

    @if ($seminars->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Seminare" icon="calendar">
            <p>Wenn Sie ein Seminar anfragen, erscheint es hier mit Stand, Terminen und Kostenvoranschlag.</p>
            <x-slot:actions><x-vdbs.actions.button :href="route('school.requests.create')">Seminar anfragen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @endif

    @foreach ($seminars as $seminar)
        @php($quote = $seminar->quotes->first())
        <article style="margin-bottom:var(--space-6);padding:var(--space-5);background:var(--bg-surface)" aria-labelledby="seminar-{{ $seminar->id }}-title">
            <h2 id="seminar-{{ $seminar->id }}-title" style="margin-top:0;font-size:var(--font-size-h3)">{{ $seminar->title }}
                <x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge>
            </h2>

            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Leistung">{{ $seminar->type->label() }}</x-vdbs.data.detail-item>
                @if ($seminar->grade_level)<x-vdbs.data.detail-item label="Klassenstufe">{{ $seminar->grade_level }}</x-vdbs.data.detail-item>@endif
                @if ($seminar->expected_participants)<x-vdbs.data.detail-item label="Erwartete Teilnehmende">{{ $seminar->expected_participants }}</x-vdbs.data.detail-item>@endif
                <x-vdbs.data.detail-item label="Termine">@forelse ($seminar->sessions as $session){{ $session->period() }}<br>@empty noch nicht festgelegt @endforelse</x-vdbs.data.detail-item>
                @if ($quote)<x-vdbs.data.detail-item label="Kostenvoranschlag">{{ \App\Modules\Seminars\Support\Money::format($quote->total_cents) }} <a href="{{ route('school.quotes.print', $quote) }}">ansehen und drucken</a></x-vdbs.data.detail-item>@endif
            </x-vdbs.data.detail-list>

            @if ($seminar->status === \App\Modules\Seminars\Enums\SeminarStatus::Scheduled && $quote)
                @if ($confirmed[$seminar->id])
                    <p><x-vdbs.data.badge tone="success">Von Ihnen bestätigt</x-vdbs.data.badge> Die Koordination bestätigt das Seminar endgültig.</p>
                @else
                    <p>Bitte prüfen Sie Termin und Kostenvoranschlag. Wenn beides passt, bestätigen Sie es hier.</p>
                    <form method="post" action="{{ route('school.seminars.confirm', $seminar) }}">@csrf<x-vdbs.actions.button type="submit" icon="check">Termin und Kostenvoranschlag bestätigen</x-vdbs.actions.button></form>
                @endif
            @endif

            @php($teacherLink = $seminar->feedbackLinks()->where('audience', 'teachers')->orderByDesc('id')->get()->first(fn ($candidate): bool => app(\App\Modules\Feedback\Services\FeedbackLinks::class)->state($candidate) === 'open'))
            @if ($teacherLink)
                <p style="margin-top:var(--space-4)"><strong>Wie war das Seminar?</strong> Bitte nehmen Sie sich ein paar Minuten für die anonyme Rückmeldung: <a href="{{ $teacherLink->url() }}">zum Fragebogen für Lehrkräfte</a>.</p>
            @endif

            <details style="margin-top:var(--space-4)">
                <summary>Nachricht an die Koordination schicken</summary>
                <x-vdbs.forms.form :action="route('school.seminars.feedback', $seminar)" :summary="false">
                    <x-vdbs.forms.textarea :id="'note-'.$seminar->id" name="note" label="Ihre Nachricht" :required="true" maxlength="1000" :rows="3" hint="z. B. Terminwunsch, Änderung der Teilnehmerzahl oder eine Frage zum Kostenvoranschlag" />
                    <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="send">Senden</x-vdbs.actions.button></x-slot:actions>
                </x-vdbs.forms.form>
            </details>
        </article>
    @endforeach
</x-layouts.app>
