<?php

namespace App\Modules\MailBuilder\Support;

use InvalidArgumentException;

/**
 * Das Register aller Systemmails mit festem Text. Jede hat einen Standardtext im Code; Änderungen aus dem Builder liegen in der Datenbank
 * (mail_templates.system_key) und lassen sich zurücksetzen. Fehlt die Änderung oder ist sie unbrauchbar, gilt der Standardtext.
 */
final class SystemMails
{
    /** Gilt für jede Systemmail. @var array<string, array{label: string, sample: string}> */
    public const COMMON = [
        'anrede' => ['label' => 'Anrede, z. B. „Guten Tag Anna“', 'sample' => 'Guten Tag Anna'],
        'verein' => ['label' => 'Name des Vereins', 'sample' => 'Verband für Demokratiebildung und Bibliotheken an Schulen e. V.'],
        'portal' => ['label' => 'Adresse des Portals', 'sample' => 'https://portal.example'],
        'jahr' => ['label' => 'Aktuelles Jahr', 'sample' => '2027'],
        'heute' => ['label' => 'Heutiges Datum', 'sample' => '01.03.2027'],
    ];

    /** @var array<string, SystemMail>|null */
    private static ?array $all = null;

    /** @return array<string, SystemMail> */
    public static function all(): array
    {
        return self::$all ??= self::build();
    }

    public static function find(string $key): ?SystemMail
    {
        return self::all()[$key] ?? null;
    }

    public static function get(string $key): SystemMail
    {
        return self::find($key) ?? throw new InvalidArgumentException('Unbekannte Systemmail: '.$key);
    }

    /** @return array<string, list<SystemMail>> Gruppe => Mails */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::all() as $mail) {
            $groups[$mail->group][] = $mail;
        }

        return $groups;
    }

    /** @return array<string, SystemMail> */
    private static function build(): array
    {
        $greeting = ['type' => 'text', 'props' => ['html' => '<p>{anrede},</p>']];
        $t = fn (string $html): array => ['type' => 'text', 'props' => ['html' => $html]];
        $b = fn (string $label, string $url, ?array $visible = null): array => ['type' => 'button', 'props' => ['label' => $label, 'url' => $url, 'align' => 'left', 'style' => 'primary'], 'visible_if' => $visible];
        $when = fn (string $name, string $mode = 'set'): array => ['placeholder' => $name, 'when' => $mode];
        $ph = fn (string $label, string|array $sample, bool $protected = false, bool $raw = false): array => ['label' => $label, 'sample' => $sample, 'protected' => $protected, 'raw' => $raw];
        $url = fn (string $label): array => $ph($label, 'https://portal.example/link/abc', true);

        $mails = [];
        $add = function (SystemMail $mail) use (&$mails): void {
            $mails[$mail->key] = $mail;
        };

        // Konto und Anmeldung
        $add(new SystemMail('access.password_reset', 'Passwort zurücksetzen', 'Konto und Anmeldung', 'Link zum Zurücksetzen des Passworts.', 'Passwort für das Vereinsportal zurücksetzen',
            ['url' => $url('Link zum Zurücksetzen'), 'minuten' => $ph('Gültigkeit in Minuten', '60')],
            [$greeting, $t('<p>für Ihr Konto im Vereinsportal wurde das Zurücksetzen des Passworts angefordert. Über diesen Link vergeben Sie ein neues Passwort:</p>'), $b('Neues Passwort festlegen', '{url}'), $t('<p>Der Link ist {minuten} Minuten gültig. Wenn Sie das nicht angefordert haben, brauchen Sie nichts zu tun; Ihr Passwort bleibt unverändert.</p>')], security: true));

        $add(new SystemMail('access.two_factor_code', 'Sicherheitscode (Anmeldung)', 'Konto und Anmeldung', 'Code für die Zwei-Faktor-Anmeldung per E-Mail.', 'Ihr Sicherheitscode für das Vereinsportal',
            ['code' => $ph('Sicherheitscode', '123456', true), 'minuten' => $ph('Gültigkeit in Minuten', '10')],
            [$greeting, $t('<p>Ihr Sicherheitscode für die Anmeldung im Vereinsportal lautet:</p>'), ['type' => 'code', 'props' => ['label' => '', 'value' => '{code}']], $t('<p>Der Code ist {minuten} Minuten gültig und kann nur einmal verwendet werden.</p><p>Wenn Sie sich nicht angemeldet haben, ignorieren Sie diese Nachricht und ändern Sie bei Bedarf Ihr Passwort.</p>')], security: true));

        $add(new SystemMail('access.email_change_confirm', 'Neue E-Mail-Adresse bestätigen', 'Konto und Anmeldung', 'An die neue Adresse: Bestätigung der E-Mail-Änderung.', 'Neue E-Mail-Adresse für das Vereinsportal bestätigen',
            ['url' => $url('Bestätigungslink'), 'neue_adresse' => $ph('Neue E-Mail-Adresse', 'neu@example.org'), 'stunden' => $ph('Gültigkeit in Stunden', '24')],
            [$greeting, $t('<p>für ein Konto im Vereinsportal wurde {neue_adresse} als neue E-Mail-Adresse angegeben. Bestätigen Sie die Adresse über diesen Link:</p>'), $b('E-Mail-Adresse bestätigen', '{url}'), $t('<p>Der Link ist {stunden} Stunden gültig und nur nach der Anmeldung im Portal nutzbar. Wenn Sie das nicht veranlasst haben, ignorieren Sie diese Nachricht.</p>')], security: true));

        $add(new SystemMail('access.email_change_notice', 'Hinweis auf E-Mail-Änderung', 'Konto und Anmeldung', 'An die alte Adresse: Warnung vor Missbrauch.', 'Änderung Ihrer E-Mail-Adresse im Vereinsportal beantragt',
            ['neue_adresse' => $ph('Neue E-Mail-Adresse', 'neu@example.org')],
            [$greeting, $t('<p>für Ihr Konto im Vereinsportal wurde beantragt, die E-Mail-Adresse auf {neue_adresse} zu ändern. Die Änderung gilt erst, wenn sie über die neue Adresse bestätigt wird.</p><p>Waren Sie das nicht? Ändern Sie dann umgehend Ihr Passwort im Portal unter Konto › Passwort.</p>')], security: true));

        $add(new SystemMail('access.account_deletion_requested', 'Kontolöschung beantragt', 'Konto und Anmeldung', 'Bestätigung des Löschantrags mit Link zum Abbrechen.', 'Löschung Ihres Kontos im Vereinsportal beantragt',
            ['url' => $url('Link zum Abbrechen'), 'tage' => $ph('Wartefrist in Tagen', '14'), 'bis' => $ph('Datum der endgültigen Löschung', '15.03.2027')],
            [$greeting, $t('<p>für Ihr Konto im Portal des {verein} wurde die Löschung beantragt. Das Konto ist ab sofort gesperrt und wird am {bis} ({tage} Tage nach dem Antrag) endgültig gelöscht.</p><p>Wenn Sie es sich anders überlegen, brechen Sie die Löschung bis dahin über diesen Link ab:</p>'), $b('Löschung abbrechen', '{url}'), $t('<p>Haben Sie die Löschung nicht beantragt, brechen Sie sie bitte ebenfalls ab und ändern Sie Ihr Passwort.</p>')], security: true));

        $add(new SystemMail('access.account_deleted', 'Konto gelöscht', 'Konto und Anmeldung', 'Letzte Nachricht nach der Löschung.', 'Ihr Konto im Vereinsportal wurde gelöscht', [],
            [$greeting, $t('<p>Ihr Konto im Portal des {verein} wurde wie beantragt gelöscht. Ihre persönlichen Angaben wurden entfernt; Ihre Anmeldung und Ihre Rollen bestehen nicht mehr.</p><p>Daten, die der Verein aus rechtlichen Gründen aufbewahren muss, bleiben ohne Bezug zu Ihrem Namen erhalten.</p>')], security: true));

        $add(new SystemMail('access.invitation', 'Einladung ins Portal', 'Konto und Anmeldung', 'Einladung mit Link zum Festlegen des Passworts.', 'Einladung ins Vereinsportal',
            ['url' => $url('Einladungslink'), 'tage' => $ph('Gültigkeit in Tagen', '14')],
            [$greeting, $t('<p>Sie wurden ins Portal des {verein} eingeladen. Über diesen Link legen Sie Ihr Passwort fest und melden sich an:</p>'), $b('Einladung annehmen und Passwort festlegen', '{url}'), $t('<p>Der Link ist {tage} Tage gültig. Wenn Sie die Einladung nicht erwartet haben, ignorieren Sie diese Nachricht.</p>')], security: true));

        // Zugangsanträge
        $add(new SystemMail('access_requests.received', 'Antrag auf Zugang eingegangen', 'Zugangsanträge', 'Eingangsbestätigung an die Antragstellenden.', 'Ihr Antrag auf Zugang zum Vereinsportal ist eingegangen',
            ['nummer' => $ph('Vorgangsnummer', '1042')],
            [$greeting, $t('<p>vielen Dank, Ihr Antrag auf Zugang zum Vereinsportal ist bei uns eingegangen (Vorgang Nr. {nummer}). Die Geschäftsstelle prüft ihn und meldet sich bei Ihnen. Wenn Ihr Antrag angenommen wird, erhalten Sie eine E-Mail mit einem Link, über den Sie Ihr Passwort festlegen.</p>')]));

        $add(new SystemMail('access_requests.rejected', 'Antrag auf Zugang abgelehnt', 'Zugangsanträge', 'Ablehnung, optional mit Begründung.', 'Ihr Antrag auf Zugang zum Vereinsportal',
            ['begruendung' => $ph('Begründung (leer, wenn keine genannt wird)', 'Wir können die Zugehörigkeit nicht prüfen.'), 'kontakt_url' => $ph('Adresse der Kontaktseite', 'https://portal.example/kontakt')],
            [$greeting, $t('<p>wir können Ihren Antrag auf Zugang zum Vereinsportal leider nicht annehmen.</p>'), $t('<p>Begründung: {begruendung}</p>') + ['visible_if' => $when('begruendung')], $t('<p>Bei Fragen melden Sie sich gern bei uns: <a href="{kontakt_url}">{kontakt_url}</a></p>')]));

        $add(new SystemMail('access_requests.notice', 'Antrag auf Zugang: Adresse bekannt', 'Zugangsanträge', 'Neutrale Antwort, wenn es die Adresse schon gibt.', 'Ihr Antrag auf Zugang zum Vereinsportal',
            ['login_url' => $ph('Adresse der Anmeldung', 'https://portal.example/login'), 'reset_url' => $ph('Adresse zum Zurücksetzen des Passworts', 'https://portal.example/passwort'), 'kontakt_url' => $ph('Adresse der Kontaktseite', 'https://portal.example/kontakt')],
            [$greeting, $t('<p>zu dieser E-Mail-Adresse wurde ein Antrag auf Zugang zum Vereinsportal gestellt. Dazu gibt es bei uns bereits einen Eintrag. Wenn Sie schon ein Konto haben, können Sie sich hier anmelden: <a href="{login_url}">{login_url}</a>. Ihr Passwort vergessen? <a href="{reset_url}">Passwort zurücksetzen</a>.</p><p>Wenn Sie den Antrag nicht gestellt haben, können Sie diese Nachricht ignorieren. Bei Fragen: <a href="{kontakt_url}">{kontakt_url}</a></p>')]));

        $add(new SystemMail('access_requests.new', 'Neuer Antrag auf Zugang (Geschäftsstelle)', 'Zugangsanträge', 'Hinweis an die Geschäftsstelle, ohne Angaben zur Person.', 'Neuer Antrag auf Zugang im Vereinsportal',
            ['url' => $url('Link zum Antrag')],
            [$greeting, $t('<p>im Vereinsportal wartet ein neuer Antrag auf Zugang auf Ihre Prüfung.</p>'), $b('Antrag ansehen', '{url}'), $t('<p>Diese Nachricht enthält aus Datenschutzgründen keine Angaben zur Person.</p>')]));

        return $mails;
    }
}
