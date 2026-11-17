<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag,</p>
    @php
        $sessions = $seminar->sessions->sortBy('starts_at');
        $school = $seminar->partner?->name;
    @endphp
    @switch ($kind)
        @case ('school_received')
            <p>wir haben Ihre Anfrage für das Seminar <strong>{{ $seminar->title }}</strong> erhalten. Die Geschäftsstelle prüft sie und meldet sich bei Ihnen.</p>
            @break
        @case ('school_question')
            <p>zu Ihrer Anfrage für das Seminar <strong>{{ $seminar->title }}</strong> haben wir eine Rückfrage:</p>
            <blockquote style="margin:0 0 12px;padding:8px 12px;border-left:3px solid #94a3b8;">{!! nl2br(e((string) $extra)) !!}</blockquote>
            <p>Bitte antworten Sie über das Portal oder auf diese Mail.</p>
            @break
        @case ('school_rejected')
            <p>wir können Ihre Anfrage für das Seminar <strong>{{ $seminar->title }}</strong> leider nicht annehmen.</p>
            @if (filled($extra))<p>Begründung: {{ $extra }}</p>@endif
            @break
        @case ('school_quote')
            <p>für Ihr Seminar <strong>{{ $seminar->title }}</strong> liegt der Kostenvoranschlag im Portal bereit. Bitte prüfen und bestätigen Sie ihn dort.</p>
            @break
        @case ('school_briefing')
            <p>hier der Ablauf für Ihr Seminar <strong>{{ $seminar->title }}</strong>.</p>
            @if ($sessions->isNotEmpty())<p>Termine:</p><ul>@foreach ($sessions as $session)<li>{{ $session->period() }}@if ($session->location), {{ $session->location }}@endif</li>@endforeach</ul>@endif
            @if (filled($extra))<p>{!! nl2br(e((string) $extra)) !!}</p>@endif
            @break
        @case ('school_cancelled')
            <p>das Seminar <strong>{{ $seminar->title }}</strong> wurde abgesagt.@if (filled($extra)) Grund: {{ $extra }}@endif Die Geschäftsstelle meldet sich zum weiteren Vorgehen.</p>
            @break
        @case ('school_rescheduled')
        @case ('team_rescheduled')
            <p>die Termine für das Seminar <strong>{{ $seminar->title }}</strong> haben sich geändert. Die neuen Termine:</p>
            <ul>@foreach ($sessions as $session)<li>{{ $session->period() }}@if ($session->location), {{ $session->location }}@endif</li>@endforeach</ul>
            @break
        @case ('school_report')
            <p>das Seminar <strong>{{ $seminar->title }}</strong> ist durchgeführt. Wir senden Ihnen den Ergebnisbericht.</p>
            @if ($seminar->results_share_url)<p>Die Ergebnisse finden Sie hier: <a href="{{ $seminar->results_share_url }}">{{ $seminar->results_share_url }}</a>@if ($seminar->results_expires_at) (verfügbar bis {{ $seminar->results_expires_at->format('d.m.Y') }})@endif</p>@endif
            @if (filled($extra))<p>{!! nl2br(e((string) $extra)) !!}</p>@endif
            <p>Wir freuen uns über Ihre Rückmeldung zum Seminar. Sie finden das Formular im Portal.</p>
            @break
        @case ('report_reminder')
            <p>als Seminarleitung laden Sie für das Seminar <strong>{{ $seminar->title }}</strong> bitte bis zum <strong>{{ $seminar->report_due_at?->format('d.m.Y') }}</strong> die Verträge und den Ergebnisbericht im Portal hoch.</p>
            @break
        @case ('results_expiring')
            <p>der Ergebnislink für die Schule zum Seminar <strong>{{ $seminar->title }}</strong> läuft am <strong>{{ $seminar->results_expires_at?->format('d.m.Y') }}</strong> ab. Bitte klären Sie, ob er verlängert oder der Ordner danach gelöscht werden soll. Gelöscht wird nichts von selbst.</p>
            @break
        @case ('report_overdue')
            <p>für das Seminar <strong>{{ $seminar->title }}</strong> fehlen Verträge und Ergebnisbericht. Der Stichtag war der {{ $seminar->report_due_at?->format('d.m.Y') }}. Bitte fragen Sie bei der Seminarleitung nach.</p>
            @break
        @case ('released')
            <p>das Seminar <strong>{{ $seminar->title }}</strong> ({{ $school }}) ist freigegeben. Sie können es jetzt planen: Termine, Teamgröße und Ausschreibung.</p>
            @break
        @case ('tender')
            <p>für das Seminar <strong>{{ $seminar->title }}</strong> ({{ $seminar->type->label() }}@if ($school), {{ $school }}@endif) wird ein Team gesucht.</p>
            @if ($seminar->application_deadline)<p>Sie können bis zum <strong>{{ $seminar->application_deadline->format('d.m.Y') }}</strong> Interesse bekunden.</p>@endif
            @break
        @case ('lead')
            <p>die Koordination hat Sie für das Seminar <strong>{{ $seminar->title }}</strong> als Seminarleitung bestimmt. Sie laden nach dem Seminar die Verträge und den Ergebnisbericht im Portal hoch.</p>
            @break
        @case ('onboarding')
            <p>zum Seminar <strong>{{ $seminar->title }}</strong> gibt es ein Onboarding per Videokonferenz@if ($seminar->onboarding_at) am <strong>{{ $seminar->onboarding_at->format('d.m.Y, H:i') }} Uhr</strong>@endif.</p>
            @if ($seminar->onboarding_link)<p>Link zur Videokonferenz: <a href="{{ $seminar->onboarding_link }}">{{ $seminar->onboarding_link }}</a></p>@endif
            @if (filled($extra))<p>{!! nl2br(e((string) $extra)) !!}</p>@endif
            @break
    @endswitch
    <p><a href="{{ $url }}">Im Portal ansehen</a></p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
