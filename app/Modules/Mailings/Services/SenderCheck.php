<?php

namespace App\Modules\Mailings\Services;

use Closure;

/**
 * Prüft die Absenderdomain per DNS: SPF, DMARC und, wenn ein Selektor eingestellt ist, DKIM. Das Ergebnis ist eine Ampel mit Erklärung und sperrt nichts.
 * Die DNS-Abfrage ist austauschbar (Closure host => Liste der TXT-Einträge), damit sie sich ohne Netz testen lässt.
 */
final class SenderCheck
{
    /** @param  ?Closure(string): list<string>  $txt */
    public function __construct(private readonly ?Closure $txt = null) {}

    /** @return list<array{label: string, status: string, detail: string}> status: ok, warning, error, info */
    public function run(): array
    {
        $address = (string) config('mail.from.address');
        $domain = str_contains($address, '@') ? mb_strtolower(substr(strrchr($address, '@'), 1)) : '';

        if ($domain === '' || ! str_contains($domain, '.')) {
            return [['label' => 'Absenderdomain', 'status' => 'error', 'detail' => 'Die Absenderadresse (MAIL_FROM_ADDRESS) hat keine gültige Domain.']];
        }

        $checks = [['label' => 'Absenderdomain', 'status' => 'info', 'detail' => $domain]];
        $checks[] = $this->spf($domain);
        $checks[] = $this->dmarc($domain);

        $selector = (string) config('portal.mailings.dkim_selector', '');
        $checks[] = $selector !== '' ? $this->dkim($domain, $selector) : ['label' => 'DKIM', 'status' => 'info', 'detail' => 'Kein Selektor eingestellt (MAILINGS_DKIM_SELECTOR). Den Selektor nennt der Versanddienst.'];

        return $checks;
    }

    /** @return array{label: string, status: string, detail: string} */
    private function spf(string $domain): array
    {
        $record = $this->first($domain, 'v=spf1');

        return match (true) {
            $record === null => ['label' => 'SPF', 'status' => 'error', 'detail' => 'Kein SPF-Eintrag gefunden. Ohne ihn landen Mails eher im Spam.'],
            str_contains($record, '-all') => ['label' => 'SPF', 'status' => 'ok', 'detail' => 'Vorhanden und streng (-all).'],
            str_contains($record, '~all') => ['label' => 'SPF', 'status' => 'ok', 'detail' => 'Vorhanden (~all, weiche Ablehnung).'],
            default => ['label' => 'SPF', 'status' => 'warning', 'detail' => 'Vorhanden, endet aber nicht mit -all oder ~all. Ein Fremder könnte in Ihrem Namen senden.'],
        };
    }

    /** @return array{label: string, status: string, detail: string} */
    private function dmarc(string $domain): array
    {
        $record = $this->first('_dmarc.'.$domain, 'v=DMARC1');

        return match (true) {
            $record === null => ['label' => 'DMARC', 'status' => 'warning', 'detail' => 'Kein DMARC-Eintrag gefunden. Große Anbieter erwarten ihn zunehmend.'],
            preg_match('/\bp=none\b/i', $record) === 1 => ['label' => 'DMARC', 'status' => 'ok', 'detail' => 'Vorhanden, Richtlinie „none“ (nur beobachten). Später auf „quarantine“ stellen.'],
            default => ['label' => 'DMARC', 'status' => 'ok', 'detail' => 'Vorhanden mit Richtlinie.'],
        };
    }

    /** @return array{label: string, status: string, detail: string} */
    private function dkim(string $domain, string $selector): array
    {
        $record = $this->first($selector.'._domainkey.'.$domain, 'v=DKIM1');

        return $record === null
            ? ['label' => 'DKIM', 'status' => 'error', 'detail' => 'Zum Selektor „'.$selector.'“ gibt es keinen DKIM-Eintrag.']
            : ['label' => 'DKIM', 'status' => 'ok', 'detail' => 'Eintrag zum Selektor „'.$selector.'“ vorhanden.'];
    }

    private function first(string $host, string $prefix): ?string
    {
        foreach ($this->lookup($host) as $record) {
            if (stripos(ltrim($record), $prefix) === 0) {
                return $record;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function lookup(string $host): array
    {
        if ($this->txt !== null) {
            return ($this->txt)($host);
        }

        $records = @dns_get_record($host, DNS_TXT);

        return is_array($records) ? array_values(array_map(fn (array $r): string => (string) ($r['txt'] ?? ''), $records)) : [];
    }
}
