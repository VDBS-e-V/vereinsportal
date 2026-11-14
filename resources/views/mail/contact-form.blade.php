<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    @if ($copy)
        <p>Guten Tag {{ $person['first_name'] }},</p>
        <p>vielen Dank für Ihre Nachricht über das Kontaktformular des Vereinsportals. Zur Kenntnis hier eine Kopie Ihrer Angaben. Sie ging an: <strong>{{ $recipientLabel }}</strong>.</p>
    @else
        <p>Über das Kontaktformular des Vereinsportals ist eine Eingabe für <strong>{{ $recipientLabel }}</strong> eingegangen.</p>
    @endif

    <p>
        <strong>Formular:</strong> {{ $formName }}@if (filled($topicTitle)) <br><strong>Thema:</strong> {{ $topicTitle }}@endif
        <br><strong>Von:</strong> {{ $person['first_name'] }} {{ $person['last_name'] }} ({{ $person['email'] }})
        @if (filled($person['phone'] ?? null)) <br><strong>Telefon:</strong> {{ $person['phone'] }}@endif
        @if (filled($person['organization'] ?? null)) <br><strong>Schule / Organisation:</strong> {{ $person['organization'] }}@endif
        @if (filled($subjectLine)) <br><strong>Betreff:</strong> {{ $subjectLine }}@endif
    </p>

    @if (filled($body))
        <p style="white-space: pre-line;">{{ $body }}</p>
    @endif

    @if ($answers !== [])
        <table style="border-collapse: collapse;">
            @foreach ($answers as $answer)
                <tr>
                    <td style="padding: 2px 12px 2px 0; vertical-align: top;"><strong>{{ $answer['label'] }}</strong></td>
                    <td style="padding: 2px 0; white-space: pre-line;">{{ $answer['value'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @unless ($copy)
        <p>Mit „Antworten“ erreichen Sie die absendende Person direkt.</p>
    @endunless
</body>
</html>
