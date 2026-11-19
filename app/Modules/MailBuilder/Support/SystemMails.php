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

        // Mitgliedschaft
        $add(new SystemMail('membership.verify', 'E-Mail-Adresse bestätigen (Antrag)', 'Mitgliedschaft', 'Bestätigung der Adresse nach dem Online-Antrag.', 'Bitte bestätigen Sie Ihre E-Mail-Adresse',
            ['url' => $url('Bestätigungslink'), 'stunden' => $ph('Gültigkeit in Stunden', '48')],
            [$greeting, $t('<p>vielen Dank für Ihren Antrag auf Mitgliedschaft im {verein} Bitte bestätigen Sie Ihre E-Mail-Adresse, damit Ihr Konto im Portal aktiv wird:</p>'), $b('E-Mail-Adresse bestätigen', '{url}'), $t('<p>Der Link ist {stunden} Stunden gültig. Danach entscheidet der Vorstand über Ihren Antrag. Wenn Sie den Antrag nicht gestellt haben, ignorieren Sie diese Nachricht.</p>')], security: true));

        $add(new SystemMail('membership.registration_link', 'Registrierungslink', 'Mitgliedschaft', 'Der Vorstand lädt ein Mitglied zur Registrierung ein.', 'Ihr Zugang zum Vereinsportal',
            ['url' => $url('Registrierungslink'), 'tage' => $ph('Gültigkeit in Tagen', '14')],
            [$greeting, $t('<p>der {verein} lädt Sie ein, sich für das Vereinsportal zu registrieren. Über diesen Link legen Sie Ihr Konto selbst an und vergeben Ihr Passwort:</p>'), $b('Konto registrieren', '{url}'), $t('<p>Der Link ist {tage} Tage gültig und nur für Sie bestimmt. Geben Sie ihn nicht weiter. Wenn Sie die Nachricht nicht erwartet haben, ignorieren Sie sie.</p>')], security: true));

        $add(new SystemMail('membership.application_notice', 'Antrag auf Mitgliedschaft: Eintrag besteht', 'Mitgliedschaft', 'Neutrale Antwort, wenn der Antrag nicht automatisch angelegt werden kann.', 'Ihr Antrag auf Mitgliedschaft im VDBS',
            ['konto' => $ph('Gesetzt, wenn die Person schon ein Konto hat', '1'), 'login_url' => $ph('Adresse der Anmeldung', 'https://portal.example/login'), 'reset_url' => $ph('Adresse zum Zurücksetzen des Passworts', 'https://portal.example/passwort'), 'kontakt_url' => $ph('Adresse der Kontaktseite', 'https://portal.example/kontakt')],
            [$greeting, $t('<p>für diese E-Mail-Adresse wurde ein Antrag auf Mitgliedschaft im {verein} gestellt. Wir konnten ihn nicht automatisch anlegen, weil zu Ihren Angaben schon ein Eintrag besteht.</p>'),
                $t('<p>Sie haben bereits ein Konto im Portal. Sie können sich <a href="{login_url}">hier anmelden</a>. Falls Sie Ihr Passwort vergessen haben, <a href="{reset_url}">setzen Sie es hier zurück</a>.</p>') + ['visible_if' => $when('konto')],
                $t('<p>Bitte wenden Sie sich an den Vorstand. Er kann Ihnen einen Registrierungslink für ein Online-Konto senden. Die Kontaktmöglichkeiten finden Sie <a href="{kontakt_url}">hier</a>.</p>') + ['visible_if' => $when('konto', 'empty')],
                $t('<p>Wenn Sie den Antrag nicht gestellt haben, ignorieren Sie diese Nachricht.</p>')]));

        $add(new SystemMail('membership.new_application', 'Neuer Mitgliedschaftsantrag (Vorstand)', 'Mitgliedschaft', 'Hinweis an den Vorstand, ohne Angaben zur Person.', 'Neuer Mitgliedschaftsantrag im Vereinsportal',
            ['url' => $url('Link zum Antrag')],
            [$greeting, $t('<p>im Vereinsportal wartet ein neuer Antrag auf Mitgliedschaft auf Ihre Entscheidung. Die Angaben sehen Sie nach der Anmeldung:</p>'), $b('Antrag ansehen', '{url}'), $t('<p>Diese Nachricht enthält aus Datenschutzgründen keine Angaben zur Person.</p>')]));

        // Einarbeitung
        $add(new SystemMail('onboarding.member_welcome', 'Willkommen (neue Mitglieder)', 'Einarbeitung', 'Willkommensmail, Satzung und Brief hängen an.', 'Herzlich willkommen im VDBS',
            ['beginn' => $ph('Beginn der Mitgliedschaft (leer, wenn unbekannt)', '01.03.2027'), 'dokumente' => $ph('Angehängte Dokumente', ['Satzung', 'Willkommensbrief']), 'portal_url' => $ph('Adresse des Portals (leer, wenn kein Konto)', 'https://portal.example')],
            [$greeting, $t('<p>herzlich willkommen im Verband für Demokratiebildung und Bibliotheken an Schulen!</p>'), $t('<p>Ihre Mitgliedschaft beginnt am {beginn}.</p>') + ['visible_if' => $when('beginn')],
                $t('<p>Im Anhang dieser E-Mail finden Sie:</p>') + ['visible_if' => $when('dokumente')], ['type' => 'repeat', 'props' => ['placeholder' => 'dokumente', 'template' => '{item}', 'ordered' => false]],
                $t('<p>Mit Ihrem Konto im Vereinsportal können Sie Ihre Angaben einsehen und Ihr Profil pflegen: <a href="{portal_url}">{portal_url}</a></p>') + ['visible_if' => $when('portal_url')],
                $t('<p>Bei Fragen melden Sie sich gern bei uns.</p>'), ['type' => 'signature', 'props' => ['html' => '<p>Mit freundlichen Grüßen<br>Der Vorstand<br>{verein}</p>']]], mandatory: false));

        $add(new SystemMail('onboarding.team_started', 'Einarbeitung beginnt (Teamende)', 'Einarbeitung', 'Hinweis an neue Teamende.', 'Ihre Einarbeitung als Teamende im VDBS',
            ['url' => $ph('Link zur Einarbeitung', 'https://portal.example/einarbeitung')],
            [$greeting, $t('<p>Sie sind jetzt als Teamende:r im Vereinsportal eingetragen. Vor Ihrem ersten Einsatz bitten wir Sie um ein paar Schritte zur Einarbeitung: Erklärungen lesen und bestätigen, außerdem Termine für die Nachweise mit der Koordination.</p>'), $b('Zu Ihrer Einarbeitung im Portal', '{url}')], mandatory: false));

        $add(new SystemMail('onboarding.team_reminder', 'Erinnerung an die Einarbeitung', 'Einarbeitung', 'Erinnerung bei offener Einarbeitung.', 'Erinnerung: Ihre Einarbeitung als Teamende im VDBS',
            ['url' => $ph('Link zur Einarbeitung', 'https://portal.example/einarbeitung'), 'offen' => $ph('Zahl der offenen Schritte', '2'), 'gesamt' => $ph('Zahl aller Schritte', '5')],
            [$greeting, $t('<p>Ihre Einarbeitung als Teamende:r im Vereinsportal ist noch nicht abgeschlossen: {offen} von {gesamt} Schritten sind offen. Bevor Sie Seminare übernehmen können, brauchen wir diese Schritte.</p>'), $b('Zu Ihrer Einarbeitung im Portal', '{url}'), $t('<p>Wenn Sie Fragen haben, antworten Sie einfach der Koordination oder nutzen Sie das Kontaktformular im Portal.</p>')], mandatory: false));

        // Gremien
        $meeting = ['titel' => $ph('Titel der Sitzung', 'Jahresplanung'), 'datum' => $ph('Datum der Sitzung', '12.04.2027'), 'zeit' => $ph('Zeit der Sitzung', 'Montag, 12.04.2027, 18:00 bis 20:00 Uhr'), 'ort' => $ph('Ort (leer, wenn keiner)', 'Geschäftsstelle'), 'video' => $ph('Link zur Videokonferenz (leer, wenn keiner)', 'https://video.example/abc'), 'hinweis' => $ph('Hinweis des Vorstands (leer, wenn keiner)', '<p>Bitte pünktlich.</p>', false, true), 'tagesordnung' => $ph('Tagesordnung (leer, wenn nicht mitgesendet)', '<p>TOP 1 Haushalt</p>', false, true)];
        $details = [['type' => 'facts', 'props' => ['rows' => [['label' => 'Zeit', 'value' => '{zeit}'], ['label' => 'Ort', 'value' => '{ort}'], ['label' => 'Videokonferenz', 'value' => '{video}']]]], $t('{hinweis}') + ['visible_if' => $when('hinweis')], $t('<p><strong>Tagesordnung</strong></p>{tagesordnung}') + ['visible_if' => $when('tagesordnung')]];

        $add(new SystemMail('board.invitation', 'Einladung zur Vorstandssitzung', 'Gremien', 'Einladung an Vorstandsmitglieder, Kalenderdatei hängt an.', 'Einladung: {titel} am {datum}',
            $meeting + ['url' => $ph('Link zur Sitzung im Portal', 'https://portal.example/vorstand/sitzungen/1', true)],
            [$greeting, $t('<p>zur Sitzung „{titel}“ laden wir Sie herzlich ein.</p>'), ...$details, $t('<p>Im Anhang finden Sie den Termin als Kalenderdatei. Alle Unterlagen stehen nach der Anmeldung im Portal:</p>'), $b('Sitzung ansehen', '{url}')]));

        $add(new SystemMail('board.guest_invitation', 'Einladung von Gästen zur Vorstandssitzung', 'Gremien', 'Einladung an externe Gäste, ohne Portalzugang.', 'Einladung: {titel} am {datum}',
            $meeting + ['aufbewahrung_tage' => $ph('Tage bis zur Löschung der Gastangaben', '30'), 'organisation_hinweis' => $ph('Zusatz „ sowie Ihre Organisation“ (leer, wenn keine genannt)', ' sowie Ihre Organisation'), 'datenschutz_link' => $ph('Link zur Datenschutzerklärung (leer, wenn keiner)', 'https://portal.example/datenschutz')],
            [$greeting, $t('<p>der {verein} lädt Sie als Gast zur Sitzung „{titel}“ ein.</p>'), ...$details, $t('<p>Im Anhang finden Sie den Termin als Kalenderdatei. Wenn Sie nicht teilnehmen können, antworten Sie bitte kurz auf diese E-Mail.</p>'),
                $t('<p><strong>Hinweis zum Datenschutz:</strong> Der {verein} hat Ihren Namen und Ihre E-Mail-Adresse{organisation_hinweis} vom Vorstand erhalten und speichert sie nur, um Sie zu dieser Sitzung einzuladen (Art. 6 Abs. 1 lit. f DSGVO). Nur der Vorstand sieht die Angaben; Sie erhalten keinen Zugang zum Portal. Wir löschen die Angaben {aufbewahrung_tage} Tage nach der Sitzung. Sie haben das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung und Widerspruch sowie auf Beschwerde bei einer Aufsichtsbehörde.</p>'),
                $t('<p>Mehr dazu in unserer <a href="{datenschutz_link}">Datenschutzerklärung</a>.</p>') + ['visible_if' => $when('datenschutz_link')]]));

        // Kontakte
        $add(new SystemMail('contacts.access', 'Persönlicher Zugang (Kontakte)', 'Kontakte', 'Link und Code für Kontakte ohne Konto.', 'Ihr persönlicher Zugang',
            ['link' => $ph('Zugangslink', 'https://portal.example/z/abc', true), 'code' => $ph('Zugangscode', 'ABCD-EFGH', true), 'code_hinweis' => $ph('Letzte zwei Zeichen des Codes', 'GH'), 'code_url' => $ph('Adresse der Codeeingabe', 'https://portal.example/zugang'), 'abmelden_url' => $ph('Link zum Abbestellen', 'https://portal.example/abmelden/abc')],
            [$greeting, $t('<p>für Sie wurde ein persönlicher Zugang des {verein} eingerichtet, ohne eigenes Konto.</p><p><strong>So melden Sie sich an:</strong></p><p>Klicken Sie auf diesen Link:</p>'), $b('Zugang öffnen', '{link}'), $t('<p>Der Link lautet: {link}</p><p>Oder öffnen Sie <a href="{code_url}">{code_url}</a> und geben Sie dort Ihre E-Mail-Adresse und diesen Code ein:</p>'), ['type' => 'code', 'props' => ['label' => '', 'value' => '{code}']],
                $t('<p>Der Zugang gehört nur Ihnen. Bitte geben Sie Link und Code nicht weiter. Bei Fragen am Telefon nennen wir Ihnen nur die letzten zwei Zeichen des Codes zur Kontrolle: …{code_hinweis}</p>'), ['type' => 'divider', 'props' => []],
                $t('<p>Sie erhalten diese Nachricht, weil Ihre Adresse auf der Mailingliste des {verein} steht. <a href="{abmelden_url}">Mitteilungen dieser Art abbestellen</a></p>')], security: true, mandatory: false));

        $add(new SystemMail('contact_form.mail', 'Kontaktformular', 'Kontakte', 'Eingang aus dem Kontaktformular an die zuständige Stelle oder als Kopie.', '{praefix}{titel}',
            ['praefix' => $ph('Beginn des Betreffs', '[Kontaktformular] '), 'titel' => $ph('Betreff oder Formularname', 'Rückruf erbeten'), 'kopie' => $ph('Gesetzt bei der Kopie an die absendende Person', ''), 'vorname' => $ph('Vorname der absendenden Person', 'Erika'), 'empfaenger' => $ph('Zuständige Stelle', 'Geschäftsstelle'), 'formular' => $ph('Name des Formulars', 'Kontakt'), 'thema' => $ph('Thema (leer, wenn keines)', 'Mitgliedschaft'), 'von' => $ph('Name und Adresse der absendenden Person', 'Erika Muster (erika@example.org)'), 'telefon' => $ph('Telefon', '0123 456'), 'organisation' => $ph('Schule oder Organisation', 'Schule am Park'), 'betreff' => $ph('Betreff der Nachricht', 'Rückruf'), 'nachricht' => $ph('Nachricht', '<p>Bitte um Rückruf.</p>', false, true), 'antworten' => $ph('Weitere Angaben', ['<strong>Anliegen:</strong> Mitgliedschaft'], false, true)],
            [['type' => 'text', 'props' => ['html' => '<p>Guten Tag {vorname},</p><p>vielen Dank für Ihre Nachricht über das Kontaktformular des Vereinsportals. Zur Kenntnis hier eine Kopie Ihrer Angaben. Sie ging an: <strong>{empfaenger}</strong>.</p>'], 'visible_if' => $when('kopie')],
                ['type' => 'text', 'props' => ['html' => '<p>Über das Kontaktformular des Vereinsportals ist eine Eingabe für <strong>{empfaenger}</strong> eingegangen.</p>'], 'visible_if' => $when('kopie', 'empty')],
                ['type' => 'facts', 'props' => ['rows' => [['label' => 'Formular', 'value' => '{formular}'], ['label' => 'Thema', 'value' => '{thema}'], ['label' => 'Von', 'value' => '{von}'], ['label' => 'Telefon', 'value' => '{telefon}'], ['label' => 'Schule / Organisation', 'value' => '{organisation}'], ['label' => 'Betreff', 'value' => '{betreff}']]]],
                $t('{nachricht}') + ['visible_if' => $when('nachricht')], ['type' => 'repeat', 'props' => ['placeholder' => 'antworten', 'template' => '{item}', 'ordered' => false]],
                $t('<p>Mit „Antworten“ erreichen Sie die absendende Person direkt.</p>') + ['visible_if' => $when('kopie', 'empty')]]));

        // Anträge, Aufgaben, Tickets
        $add(new SystemMail('forms.status', 'Antrag: Statusmitteilung', 'Anträge und Aufgaben', 'Hinweis zu einem Antrag, ohne Beträge und Belege.', '{betreff} ({nummer})',
            ['betreff' => $ph('Betreff der Mitteilung', 'Ihr Antrag wurde angenommen'), 'nummer' => $ph('Nummer des Antrags', 'A-2027-0001'), 'art' => $ph('Art des Antrags', 'Kostenerstattung'), 'text' => $ph('Kurzer Text', 'Ihr Antrag wurde angenommen.')],
            [$greeting, $t('<p>{text}</p><p><strong>{art} {nummer}</strong></p><p>Details sehen Sie nach der Anmeldung im Portal unter „Anträge“.</p>')], mandatory: false));

        $taskText = fn (string $intro): array => [$greeting, $t($intro), ['type' => 'repeat', 'props' => ['placeholder' => 'aufgaben', 'template' => '{item}', 'ordered' => false]], $b('Meine Aufgaben ansehen', '{url}'), $t('<p>Sie erhalten diese Nachricht, weil Sie sie in Ihrem Konto unter „Benachrichtigungen“ eingeschaltet haben. Dort können Sie sie jederzeit wieder ausschalten.</p>')];
        $taskHolders = ['von' => $ph('Wer die Aufgaben zugewiesen hat', 'Max Muster'), 'aufgaben' => $ph('Aufgaben', ['Ticketsystem prüfen (Projekt „Technik“, fällig am 05.03.2027)'], false, true), 'url' => $ph('Link zu den Aufgaben', 'https://portal.example/aufgaben')];

        $add(new SystemMail('tasks.assigned_one', 'Aufgabe zugewiesen', 'Anträge und Aufgaben', 'Eine neue Aufgabe wurde zugewiesen.', 'Neue Aufgabe: {titel}',
            $taskHolders + ['titel' => $ph('Titel der Aufgabe', 'Ticketsystem prüfen')], $taskText('<p>{von} hat Ihnen im Vereinsportal eine Aufgabe zugewiesen:</p>'), mandatory: false));

        $add(new SystemMail('tasks.assigned_many', 'Aufgaben zugewiesen', 'Anträge und Aufgaben', 'Mehrere neue Aufgaben wurden zugewiesen.', '{anzahl} neue Aufgaben im Vereinsportal',
            $taskHolders + ['anzahl' => $ph('Zahl der Aufgaben', '3')], $taskText('<p>{von} hat Ihnen im Vereinsportal {anzahl} Aufgaben zugewiesen:</p>'), mandatory: false));

        $add(new SystemMail('tasks.due', 'Fällige Aufgaben', 'Anträge und Aufgaben', 'Erinnerung an bald fällige und überfällige Aufgaben.', 'Erinnerung: Aufgaben im Vereinsportal sind fällig',
            ['ueberfaellig' => $ph('Überfällige Aufgaben', ['Bericht schreiben (Projekt „Technik“, fällig war der 01.03.2027)'], false, true), 'bald' => $ph('Bald fällige Aufgaben', ['Plan prüfen (Projekt „Technik“, fällig am 09.03.2027)'], false, true), 'url' => $ph('Link zu den Aufgaben', 'https://portal.example/aufgaben')],
            [$greeting, $t('<p><strong>Überfällig:</strong></p>') + ['visible_if' => $when('ueberfaellig')], ['type' => 'repeat', 'props' => ['placeholder' => 'ueberfaellig', 'template' => '{item}', 'ordered' => false]], $t('<p><strong>Bald fällig:</strong></p>') + ['visible_if' => $when('bald')], ['type' => 'repeat', 'props' => ['placeholder' => 'bald', 'template' => '{item}', 'ordered' => false]],
                $b('Meine Aufgaben ansehen', '{url}'), $t('<p>Sie erhalten diese Erinnerung, weil Sie sie in Ihrem Konto unter „Benachrichtigungen“ eingeschaltet haben, und zwar einmal je Aufgabe. Dort können Sie sie jederzeit wieder ausschalten.</p>')], mandatory: false));

        $ticket = ['nummer' => $ph('Ticketnummer', 'T-2027-0001'), 'betreff' => $ph('Betreff des Tickets', 'Frage zum Portal'), 'von' => $ph('Wer geschrieben hat (leer, wenn unbekannt)', 'Erika Muster'), 'url' => $ph('Link zum Ticket', 'https://portal.example/tickets/1', true)];
        $tail = [$b('Ticket im Vereinsportal öffnen', '{url}'), $t('<p>(Anmeldung nötig) Aus Datenschutzgründen steht der Text der Nachricht nur im Portal.</p>')];

        $add(new SystemMail('tickets.new', 'Ticket: neu', 'Tickets', 'Hinweis auf ein neues Ticket.', 'Neues Ticket {nummer}: {betreff}', $ticket + ['von_zusatz' => $ph('Zusatz „ von Name“ (leer, wenn unbekannt)', ' von Erika Muster')],
            [$greeting, $t('<p>im Vereinsportal wurde ein neues Ticket angelegt{von_zusatz}: <strong>{nummer} – {betreff}</strong>.</p>'), ...$tail], mandatory: false));
        $add(new SystemMail('tickets.assigned', 'Ticket: zugewiesen', 'Tickets', 'Ein Ticket wurde zugewiesen.', 'Ticket {nummer} wurde Ihnen zugewiesen', $ticket,
            [$greeting, $t('<p>{von} hat Ihnen das Ticket <strong>{nummer} – {betreff}</strong> zugewiesen.</p>'), ...$tail], mandatory: false));
        $add(new SystemMail('tickets.requester_reply', 'Ticket: Antwort der anfragenden Person', 'Tickets', 'Die anfragende Person hat geschrieben.', 'Neue Nachricht im Ticket {nummer}', $ticket,
            [$greeting, $t('<p>{von} hat im Ticket <strong>{nummer} – {betreff}</strong> geschrieben.</p>'), ...$tail], mandatory: false));
        $add(new SystemMail('tickets.reply', 'Ticket: Antwort des Teams', 'Tickets', 'Das Team hat geantwortet.', 'Antwort auf Ihr Ticket {nummer}', $ticket,
            [$greeting, $t('<p>das Team hat auf Ihr Ticket <strong>{nummer} – {betreff}</strong> geantwortet.</p>'), ...$tail], mandatory: false));

        // Seminare
        $add(new SystemMail('quotes.sent', 'Kostenvoranschlag an die Schule', 'Seminare', 'Kostenvoranschlag mit PDF im Anhang.', 'Kostenvoranschlag {nummer}: {titel}',
            ['nummer' => $ph('Seminarnummer', 'S-2027-0042'), 'version' => $ph('Version des Kostenvoranschlags', '1'), 'titel' => $ph('Titel des Seminars', 'Medientag'), 'url' => $ph('Link zum Seminar im Portal', 'https://portal.example/schulen/seminare/1', true)],
            [$greeting, $t('<p>anbei erhalten Sie den Kostenvoranschlag {nummer} (Version {version}) für das Seminar <strong>{titel}</strong>.</p><p>Sie können den Kostenvoranschlag im Portal annehmen, ablehnen oder uns eine Frage dazu schicken:</p>'), $b('Seminar im Vereinsportal öffnen', '{url}'), $t('<p>(Anmeldung nötig)</p>')]));

        $add(new SystemMail('seminars.notice', 'Mitteilung zu einem Seminar (Rahmen)', 'Seminare', 'Gemeinsamer Rahmen für alle Seminarmitteilungen; der Text der einzelnen Anlässe kommt aus dem Ablauf.', '{betreff}',
            ['betreff' => $ph('Betreff des Anlasses', 'Zusage: Medientag'), 'absaetze' => $ph('Text des Anlasses', '<p>Sie sind als Teamende:r bestätigt.</p>', true, true), 'link' => $ph('Link ins Portal (leer, wenn keiner)', 'https://portal.example/teamende/seminare'), 'link_text' => $ph('Beschriftung des Links', 'Im Vereinsportal öffnen')],
            [$greeting, $t('{absaetze}'), $b('{link_text}', '{link}', $when('link')), $t('<p>(Anmeldung nötig)</p>') + ['visible_if' => $when('link')]], mandatory: false));

        $add(new SystemMail('mailings.approval', 'Schreiben zur Freigabe (Vorstand)', 'Rundschreiben', 'Hinweis an den Vorstand: ein Schreiben liegt zur Freigabe vor.', 'Schreiben zur Freigabe: {betreff}',
            ['autor' => $ph('Wer das Schreiben vorgelegt hat', 'Max Muster'), 'betreff' => $ph('Betreff des Schreibens', 'Einladung zur Versammlung'), 'empfaenger' => $ph('Zahl der Empfänger', '120'), 'versand' => $ph('Geplanter Versand (leer, wenn sofort)', '12.04.2027, 09:00 Uhr'), 'url' => $ph('Link zur Freigabe', 'https://portal.example/vorstand/mitteilungen/1', true)],
            [$greeting, $t('<p>{autor} hat im Portal ein Schreiben zur Freigabe vorgelegt.</p>'), ['type' => 'facts', 'props' => ['rows' => [['label' => 'Betreff', 'value' => '{betreff}'], ['label' => 'Empfänger', 'value' => '{empfaenger}'], ['label' => 'Geplanter Versand', 'value' => '{versand}']]]], $b('Schreiben ansehen und freigeben', '{url}'), $t('<p>Sie können diese Hinweise im Portal unter Konto › Benachrichtigungen abbestellen.</p>')], mandatory: false));

        return $mails;
    }
}
