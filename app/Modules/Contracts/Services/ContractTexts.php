<?php

namespace App\Modules\Contracts\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Contracts\Models\ContractTemplate;
use App\Support\Html\RichText;

/**
 * Der Vertragstext, versioniert: Jede Fassung bleibt erhalten, ein neuer Text ist eine neue Version, bestehende Verträge behalten ihre Fassung.
 * Platzhalter in {geschweiften Klammern}; fehlende Werte erscheinen als Linie zum Ausfüllen von Hand.
 */
final class ContractTexts
{
    /** @var array<string, string> */
    public const PLACEHOLDERS = [
        'auftragnehmer_name' => 'Name, Vorname der Auftragnehmerin oder des Auftragnehmers',
        'auftragnehmer_anschrift' => 'Anschrift',
        'steuernummer' => 'Steuernummer',
        'leistung' => 'Beschreibung der Leistung',
        'verguetung' => 'Vergütung',
        'umsatzsteuer_auswahl' => 'Auswahl 19 %, 7 % oder ohne Umsatzsteuer',
        'bank' => 'Bankinstitut',
        'bic' => 'BIC',
        'iban' => 'IBAN',
        'ort' => 'Ort der Unterschrift',
        'datum' => 'Datum',
        'verein_name' => 'Name des Vereins',
        'verein_vorsitz' => 'Vorsitz',
        'verein_anschrift' => 'Anschrift des Vereins',
    ];

    /** Fassung 1: Entwurf nach den Klauseln der Vorlage. Der genaue Wortlaut der Vorlage wird vom Vorstand als neue Version eingespielt. */
    public static function defaultBody(): string
    {
        return '<p><strong>Auftraggeber:</strong> {verein_name}, {verein_anschrift}</p>'
            .'<p><strong>Auftragnehmer:in:</strong> {auftragnehmer_name}<br>{auftragnehmer_anschrift}<br>Steuernummer: {steuernummer}</p>'
            .'<h2>§ 1 Leistung</h2><p>{leistung}</p>'
            .'<h2>§ 2 Vergütung</h2><p>Die Vergütung beträgt {verguetung}. Umsatzsteuer: {umsatzsteuer_auswahl}</p>'
            .'<h2>§ 3 Selbstständigkeit</h2><p>Die Auftragnehmerin oder der Auftragnehmer ist selbstständig tätig und unterliegt bei der Durchführung keinen Weisungen des Auftraggebers hinsichtlich Zeit, Ort und Art der Leistung, soweit diese nicht durch den Zweck des Seminars vorgegeben sind.</p>'
            .'<h2>§ 4 Steuern und Sozialabgaben</h2><p>Die Auftragnehmerin oder der Auftragnehmer ist für die Abführung von Steuern und Sozialabgaben selbst verantwortlich.</p>'
            .'<h2>§ 5 Fahrtkosten</h2><p>Fahrtkosten werden nach dem Bundesreisekostengesetz erstattet.</p>'
            .'<h2>§ 6 Absage</h2><p>Bei Absage des Seminars besteht kein Anspruch auf eine Ausfallvergütung.</p>'
            .'<h2>§ 7 Bankverbindung</h2><p>Bankinstitut: {bank}<br>BIC: {bic}<br>IBAN: {iban}</p>'
            .'<p>{ort}, den {datum}</p>';
    }

    public function active(): ContractTemplate
    {
        $template = ContractTemplate::query()->where('key', ContractTemplate::KEY)->where('active', true)->orderByDesc('version')->first();

        return $template ?? ContractTemplate::query()->create([
            'key' => ContractTemplate::KEY, 'version' => 1, 'title' => 'Dienstleistungsvertrag', 'body' => self::defaultBody(), 'active' => true, 'created_at' => now(),
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, ContractTemplate> */
    public function versions(): \Illuminate\Support\Collection
    {
        $this->active();

        return ContractTemplate::query()->where('key', ContractTemplate::KEY)->orderByDesc('version')->get();
    }

    public function createVersion(string $title, string $body, User $by): ContractTemplate
    {
        $current = $this->active();
        ContractTemplate::query()->where('key', ContractTemplate::KEY)->update(['active' => false]);

        $template = ContractTemplate::query()->create([
            'key' => ContractTemplate::KEY, 'version' => $current->version + 1, 'title' => trim($title), 'body' => RichText::clean($body), 'active' => true, 'created_by_user_id' => $by->id, 'created_at' => now(),
        ]);

        AuditLog::record(AuditEvent::ContractTemplateSaved, $by, null, ['version' => $template->version]);

        return $template;
    }

    /** @return list<string> Platzhalter im Text, die es nicht gibt. */
    public function unknown(string $body): array
    {
        preg_match_all('/\{([a-z_]+)\}/', $body, $matches);

        return array_values(array_diff(array_unique($matches[1]), array_keys(self::PLACEHOLDERS)));
    }

    /**
     * Setzt die Werte ein. Fehlende Werte werden zur Linie; alles wird maskiert.
     *
     * @param  array<string, mixed>  $data  Daten des Vertrags
     * @param  array<string, string>  $club  Vereinsdaten
     */
    public function render(ContractTemplate $template, array $data, array $club): string
    {
        $blank = '<span class="contract-blank">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
        $text = fn (?string $value): string => filled($value) ? e(trim((string) $value)) : $blank;

        $vat = $data['vat_mode'] ?? null;
        $box = fn (string $mode, string $label): string => ($vat === $mode ? '&#9746;' : '&#9744;').' '.$label;

        $amount = isset($data['amount_cents']) && (int) $data['amount_cents'] > 0 ? e(\App\Modules\Seminars\Support\Money::format((int) $data['amount_cents'])) : $blank;
        $values = [
            'auftragnehmer_name' => $text($data['name'] ?? null),
            'auftragnehmer_anschrift' => $text($data['address'] ?? null),
            'steuernummer' => $text($data['tax_number'] ?? null),
            'leistung' => filled($data['service_text'] ?? null) ? nl2br(e(trim((string) $data['service_text']))) : $blank,
            'verguetung' => $amount,
            'umsatzsteuer_auswahl' => $box('19', '19 %').' &nbsp; '.$box('7', '7 %').' &nbsp; '.$box('none', 'ohne Umsatzsteuer'),
            'bank' => $text($data['bank_name'] ?? null),
            'bic' => $text($data['bic'] ?? null),
            'iban' => $text($data['iban'] ?? null),
            'ort' => $text($data['place'] ?? ($club['place'] ?? null)),
            'datum' => $text($data['date'] ?? null),
            'verein_name' => $text($club['club_name'] ?? null),
            'verein_vorsitz' => $text($club['chair'] ?? null),
            'verein_anschrift' => $text($club['address'] ?? null),
        ];

        return (string) preg_replace_callback('/\{([a-z_]+)\}/', fn (array $match): string => $values[$match[1]] ?? $match[0], $template->body);
    }
}
