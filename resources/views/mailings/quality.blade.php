@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Zustellqualität']];
    $tones = ['ok' => 'success', 'warning' => 'warning', 'error' => 'error', 'info' => 'neutral'];
    $labels = ['ok' => 'In Ordnung', 'warning' => 'Hinweis', 'error' => 'Problem', 'info' => 'Information'];
    $monthNames = [1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'];
@endphp

<x-layouts.app title="Zustellqualität" width="wide">
    <x-vdbs.layout.page-header title="Zustellqualität" :lead="'Wie gut kommen die Schreiben an? Die Tabelle zeigt je Monat die Fehlerquote, neue Sperren und Abmeldungen. Eine Fehlerquote über '.$threshold.' % ist ein Warnzeichen.'" :breadcrumb="$crumbs" />

    <x-vdbs.layout.grid :columns="2">
        <x-vdbs.data.stat label="Gesperrte Adressen" :value="$activeSuppressions" icon="triangle-alert" />
        <x-vdbs.data.stat label="Rückmeldungen des Versanddienstes" :value="$webhookConfigured ? 'Eingerichtet' : 'Nicht eingerichtet'" :tone="$webhookConfigured ? 'success' : 'neutral'" icon="mail" hint="Harte Fehler und Beschwerden sperren dann automatisch." />
    </x-vdbs.layout.grid>

    <section aria-labelledby="months-title" style="margin-top:var(--space-6)">
        <h2 id="months-title" style="font-size:var(--font-size-h3)">Die letzten zwölf Monate</h2>
        <x-vdbs.data.table caption="Zustellqualität je Monat" :columns="[['key' => 'month', 'label' => 'Monat'], ['key' => 'letters', 'label' => 'Schreiben'], ['key' => 'recipients', 'label' => 'Empfänger'], ['key' => 'sent', 'label' => 'Gesendet'], ['key' => 'failed', 'label' => 'Fehlgeschlagen'], ['key' => 'rate', 'label' => 'Fehlerquote'], ['key' => 'suppressed', 'label' => 'Neue Sperren'], ['key' => 'unsubscribed', 'label' => 'Abmeldungen']]">
            @foreach ($months as $key => $row)
                @php
                    [$year, $month] = explode('-', $key);
                    $rate = $row['recipients'] > 0 ? round($row['failed'] / $row['recipients'] * 100, 1) : null;
                    $rateText = $rate === null ? '–' : number_format($rate, 1, ',', '.').' %';
                    $high = $rate !== null && $rate > $threshold;
                @endphp
                <tr>
                    <td>{{ $monthNames[(int) $month] }} {{ $year }}</td>
                    <td>{{ $row['sent_letters'] }}</td>
                    <td>{{ $row['recipients'] }}</td>
                    <td>{{ $row['sent'] }}</td>
                    <td>{{ $row['failed'] }}</td>
                    <td>{{ $rateText }}
                        @if ($high)
                            <x-vdbs.data.badge tone="warning">Hoch</x-vdbs.data.badge>
                        @endif
                    </td>
                    <td>{{ $row['suppressed'] }}</td>
                    <td>{{ $row['unsubscribed'] }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    </section>

    <section aria-labelledby="sender-title" style="margin-top:var(--space-8)">
        <h2 id="sender-title" style="font-size:var(--font-size-h3)">Absenderprüfung</h2>
        <p>Prüft per DNS, ob die Domain der Absenderadresse SPF, DMARC und (mit eingestelltem Selektor) DKIM eingerichtet hat. Das sperrt nichts, es erklärt, woran Spam-Einstufungen liegen können.</p>
        <form method="post" action="{{ route('mailings.quality.check') }}">@csrf<x-vdbs.actions.button type="submit" icon="refresh-cw">Absender jetzt prüfen</x-vdbs.actions.button></form>

        @if ($senderChecks)
            <x-vdbs.data.table caption="Ergebnis der Absenderprüfung" :columns="[['key' => 'label', 'label' => 'Prüfung'], ['key' => 'status', 'label' => 'Ergebnis'], ['key' => 'detail', 'label' => 'Erklärung']]">
                @foreach ($senderChecks as $check)
                    <tr>
                        <td>{{ $check['label'] }}</td>
                        <td><x-vdbs.data.badge :tone="$tones[$check['status']] ?? 'neutral'">{{ $labels[$check['status']] ?? $check['status'] }}</x-vdbs.data.badge></td>
                        <td>{{ $check['detail'] }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif
    </section>
</x-layouts.app>
