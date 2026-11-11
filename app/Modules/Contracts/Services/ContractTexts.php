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
        'verein_name' => 'Name des Vereins (ausgeschrieben)',
        'verein_kurz' => 'Kurzname des Vereins (für die Unterschrift)',
        'verein_vorsitz' => 'Vorsitz',
        'verein_anschrift' => 'Anschrift des Vereins',
    ];

    /** Fassung 1: der Wortlaut der Vertragsvorlage „Dienstleistungsvertrag“ (Stand 08.07.2024), Platzhalter statt der ausgefüllten Stellen. */
    public static function defaultBody(): string
    {
        return '<p>Zwischen dem {verein_name}<br>- nachfolgend Auftraggeber genannt -</p>'
            .'<p>und Herr/Frau/Firma</p>'
            .'<p>Name, Vorname: {auftragnehmer_name}<br>Straße/ Hausnummer/ PLZ: {auftragnehmer_anschrift}<br>Steuernummer: {steuernummer}</p>'
            .'<p>- nachfolgend Auftragnehmer:in -</p>'
            .'<p>Der/die Auftragnehmer:in wird mit der Erbringung folgender Leistung:</p>'
            .'<p>{leistung}</p>'
            .'<p>beauftragt.</p>'
            .'<p>Der/die Auftragnehmer:in unterliegt keinem Weisungs- und Direktionsrecht seitens des Auftraggebers. Er/Sie hat jedoch, den ihm/ihr erteilten Auftrag ordnungsgemäß und unter Beachtung der fachlichen Vorgaben zu erfüllen. Der/die Auftragnehmer/in bestätigt, dass er/sie außer für den Auftraggeber auch für Dritte tätig ist bzw. seine/ihre Tätigkeit nicht überwiegend und nicht regelmäßig für den Auftraggeber erfolgt sowie kein Beschäftigungsverhältnis mit dem Auftraggeber unterhält.</p>'
            .'<p>Der/die Auftragnehmer/in erhält für die oben genannten Leistungen eine Vergütung in Höhe von {verguetung}.</p>'
            .'<p>Zuzüglich {umsatzsteuer_auswahl} (bitte zutreffend ankreuzen)</p>'
            .'<p>Der/die Auftragnehmer:in versichert, dass er sie - bezogen auf den ihr/ihm erteilten Auftrag - selbständig ist. Steuern und Sozialabgaben sind von dem/der Auftragnehmer:in zu entrichten. Evtl. zusätzlich anfallende Umsatzsteuer ist durch Rechnungsstellung nachzuweisen.</p>'
            .'<p>Die Erstattung von Fahrtkosten erfolgt auf der Basis des Bundesreisekostengesetzes. Bei Absage der Veranstaltung - auch kurzfristig - wird keine Ausfallvergütung gezahlt. Der Ersatz von nachgewiesenen Aufwendungen bleibt davon unberührt.</p>'
            .'<p>Bankinstitut: {bank}<br>BIC: {bic}<br>IBAN: {iban}</p>'
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
            'umsatzsteuer_auswahl' => $box('19', '19 %').' &nbsp; '.$box('7', '7 % Umsatzsteuer').' &nbsp; '.$box('none', 'ohne Umsatzsteuer'),
            'bank' => $text($data['bank_name'] ?? null),
            'bic' => $text($data['bic'] ?? null),
            'iban' => $text($data['iban'] ?? null),
            'ort' => $text($data['place'] ?? ($club['place'] ?? null)),
            'datum' => $text($data['date'] ?? null),
            'verein_name' => $text($club['club_name'] ?? null),
            'verein_kurz' => $text($club['club_short'] ?? null),
            'verein_vorsitz' => $text($club['chair'] ?? null),
            'verein_anschrift' => $text($club['address'] ?? null),
        ];

        return (string) preg_replace_callback('/\{([a-z_]+)\}/', fn (array $match): string => $values[$match[1]] ?? $match[0], $template->body);
    }
}
