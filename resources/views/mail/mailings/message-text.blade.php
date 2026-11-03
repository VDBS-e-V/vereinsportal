{!! \App\Modules\Mailings\Support\MailText::fromHtml((string) rich_text($bodyHtml)) !!}

----
@if ($mandatory)
Diese Nachricht ist eine Pflichtmitteilung des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V. an seine Mitglieder und kann nicht abbestellt werden.
@elseif ($kind === 'members')
Sie erhalten diese Nachricht als Mitglied des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V.
@elseif ($kind === 'team')
Sie erhalten diese Nachricht als Teamende:r des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V.
@elseif ($kind === 'schools')
Sie erhalten diese Nachricht als Schulkonto im Portal des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V.
@else
Diese Nachricht kommt vom Verband für Demokratiebildung und Bibliotheken an Schulen e. V.
@endif
@if ($unsubscribeUrl)

Mitteilungen dieser Art abbestellen: {{ $unsubscribeUrl }}
@endif
