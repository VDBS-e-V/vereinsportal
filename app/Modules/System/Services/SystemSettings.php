<?php

namespace App\Modules\System\Services;

/**
 * Betriebseinstellungen zum Nachlesen: Werte aus der Konfiguration mit dem Namen des .env-Schlüssels, der sie steuert.
 * Das Portal schreibt die .env nicht selbst. Geändert wird in der Datei, danach „Konfiguration leeren“ unter Aufgaben.
 */
final class SystemSettings
{
    /** @return array<string, list<array{label: string, value: string, key: ?string}>> */
    public function groups(): array
    {
        $show = static fn (mixed $value): string => match (true) {
            $value === null, $value === '' => 'nicht gesetzt (aus)',
            is_bool($value) => $value ? 'ja' : 'nein',
            default => (string) $value,
        };
        $row = static fn (string $label, mixed $value, ?string $key = null): array => ['label' => $label, 'value' => $show($value), 'key' => $key];

        return [
            'Aufbewahrungsfristen' => [
                $row('Protokoll (Tage)', config('audit.retention_days'), 'AUDIT_RETENTION_DAYS'),
                $row('Löschfrist nach Kontolöschung (Tage)', config('portal.offboarding.grace_days'), 'ACCOUNT_DELETION_GRACE_DAYS'),
                $row('Unbestätigte Mitgliedschaftsanträge (Tage)', config('portal.membership.retention.unverified_days'), 'MEMBERSHIP_RETENTION_UNVERIFIED_DAYS'),
                $row('Abgelehnte Mitgliedschaftsanträge (Tage)', config('portal.membership.retention.rejected_days'), 'MEMBERSHIP_RETENTION_REJECTED_DAYS'),
                $row('Beendete Mitgliedschaften (Tage, leer = unbegrenzt)', config('portal.membership.retention.ended_days'), 'MEMBERSHIP_RETENTION_ENDED_DAYS'),
                $row('Entschiedene Zugangsanträge (Tage)', config('portal.access_requests.retention_days'), 'ACCESS_REQUESTS_RETENTION_DAYS'),
                $row('Offene Zugangsanträge (Tage)', config('portal.access_requests.open_retention_days'), 'ACCESS_REQUESTS_OPEN_RETENTION_DAYS'),
                $row('Veranstaltungsanmeldungen (Jahre)', config('portal.events.retention_years')),
            ],
            'E-Mail' => [
                $row('Versand-Treiber', config('mail.default'), 'MAIL_MAILER'),
                $row('Server', config('mail.mailers.smtp.host'), 'MAIL_HOST'),
                $row('Port', config('mail.mailers.smtp.port'), 'MAIL_PORT'),
                $row('Absenderadresse', config('mail.from.address'), 'MAIL_FROM_ADDRESS'),
                $row('Absendername', config('mail.from.name'), 'MAIL_FROM_NAME'),
            ],
            'Betrieb' => [
                $row('Umgebung', config('app.env'), 'APP_ENV'),
                $row('Debug-Modus', config('app.debug'), 'APP_DEBUG'),
                $row('Adresse', config('app.url'), 'APP_URL'),
                $row('Zeitzone', config('app.timezone'), 'APP_TIMEZONE'),
                $row('Sitzungs-Speicher', config('session.driver'), 'SESSION_DRIVER'),
                $row('Sitzungsdauer (Minuten)', config('session.lifetime'), 'SESSION_LIFETIME'),
                $row('Cache-Speicher', config('cache.default'), 'CACHE_STORE'),
                $row('Warteschlange', config('queue.default'), 'QUEUE_CONNECTION'),
                $row('Protokoll-Kanal', config('logging.default'), 'LOG_CHANNEL'),
            ],
        ];
    }
}
