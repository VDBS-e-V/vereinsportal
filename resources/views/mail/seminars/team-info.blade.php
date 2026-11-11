<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag {{ $greetingName }},</p>
    <p>das Team für das Seminar <strong>{{ $seminar->number ? $seminar->number.' – ' : '' }}{{ $seminar->title }}</strong> ({{ $seminar->type->label() }}) steht. Hier die wichtigsten Angaben:</p>
    <p><strong>Schule:</strong> {{ $seminar->partner->name }}@if ($seminar->partner->city), {{ $seminar->partner->city }}@endif</p>
    @if ($seminar->sessions->isNotEmpty())
        <p><strong>Termine:</strong></p>
        <ul>@foreach ($seminar->sessions as $session)<li>{{ $session->period() }}@if ($session->location), {{ $session->location }}@endif</li>@endforeach</ul>
    @endif
    @if ($seminar->location_notes)
        <p><strong>Ort und Hinweise:</strong> {{ $seminar->location_notes }}</p>
    @endif
    @if ($extraText)
        <div style="border-left:3px solid #94a3b8;padding-left:12px;margin:12px 0">{!! nl2br(e($extraText)) !!}</div>
    @endif
    <p><strong>Ihr Team:</strong></p>
    <ul>
        @foreach ($team as $member)
            <li>{{ $member['name'] }}@if ($member['email']) – {{ $member['email'] }}@endif @if ($member['phone']), {{ $member['phone'] }}@endif</li>
        @endforeach
    </ul>
    <p style="color:#475569;font-size:14px">Kontaktdaten stehen nur bei Personen, die der Weitergabe zugestimmt haben.</p>
    <p><a href="{{ $url }}">Zu den Seminaren</a></p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
