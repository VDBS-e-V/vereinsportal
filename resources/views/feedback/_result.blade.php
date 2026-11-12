@php($num = fn ($v) => $v === null ? '–' : number_format((float) $v, 2, ',', ''))
<section style="margin-top:var(--space-6)">
    <h3 style="font-size:var(--font-size-h4)">{{ $result['link']->audienceLabel() }}@if ($result['link']->label) · {{ $result['link']->label }}@endif</h3>
    <p class="vdbs-hint">
        {{ $result['count'] }} Antwort(en)@if ($result['expected']) von etwa {{ $result['expected'] }} Teilnehmenden (Rücklauf {{ number_format($result['rate'], 0) }} %)@endif
        · {{ $result['archived'] ? 'anonymisiert, nur Zahlen im Archiv' : 'laufende Antworten' }}
    </p>

    @if ($result['archived'] && ! $result['enough'])
        <x-vdbs.feedback.alert variant="info">Dieser Link wurde anonymisiert. Es waren weniger als {{ $result['min'] }} Antworten da, deshalb wurde nichts archiviert.</x-vdbs.feedback.alert>
    @elseif (! $result['enough'])
        <x-vdbs.feedback.alert variant="info">Es gibt erst {{ $result['count'] }} von mindestens {{ $result['min'] }} Antworten. Bis dahin zeigen wir keine Ergebnisse, damit niemand auf Einzelne schließen kann.</x-vdbs.feedback.alert>
    @else
        @foreach ($result['questions'] as $question)
            <div style="margin-bottom:var(--space-5)">
                <h4 style="margin:0 0 var(--space-2)">{{ $question['text'] }} <span class="vdbs-hint">({{ $question['n'] }} Antworten)</span></h4>
                @if ($question['type'] === 'scale')
                    <p style="margin:0">Durchschnitt: <strong>{{ $num($question['avg']) }}</strong> von 5</p>
                    <ul style="margin:var(--space-2) 0 0;padding-left:var(--space-5)">
                        @foreach ($question['dist'] as $step => $count)
                            <li>{{ $step }} ({{ $question['labels'][$step - 1] ?? '' }}): {{ $count }}</li>
                        @endforeach
                    </ul>
                @elseif ($question['type'] === 'yesno')
                    <p style="margin:0">Ja: {{ $question['counts']['yes'] }} · Nein: {{ $question['counts']['no'] }}</p>
                @elseif (in_array($question['type'], ['choice', 'multi'], true))
                    <ul style="margin:0;padding-left:var(--space-5)">
                        @foreach ($question['counts'] as $option => $count)
                            <li>{{ $option }}: {{ $count }}</li>
                        @endforeach
                    </ul>
                @elseif ($question['type'] === 'text')
                    @if (empty($question['texts']))
                        <p class="vdbs-hint" style="margin:0">Keine Texte.</p>
                    @else
                        <ul style="margin:0;padding-left:var(--space-5)">
                            @foreach ($question['texts'] as $text)
                                <li>{{ $text }}</li>
                            @endforeach
                        </ul>
                        <p class="vdbs-hint" style="margin:var(--space-1) 0 0">Zufällige Reihenfolge, damit man die Antworten nicht einzelnen Personen zuordnen kann.</p>
                    @endif
                @endif
            </div>
        @endforeach
    @endif
</section>
