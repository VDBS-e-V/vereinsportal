<?php

namespace App\Modules\Mailings\Support;

/**
 * Mitgelieferter Grundbestand an Vorlagen und Textbausteinen (Entwürfe zur Freigabe). Alle Texte in Sie-Form, mit den Platzhaltern für Schreiben an Gruppen.
 * Sie werden nur angelegt, wenn es den Namen noch nicht gibt, und danach nie überschrieben.
 */
final class MailDefaults
{
    private const GREET = '<p>Mit freundlichen Grüßen<br>{absender}<br>{verein}</p>';

    /** @return list<array{name: string, description: string, category: string, audience: string, subject: string, body: string}> */
    public static function templates(): array
    {
        $greet = self::GREET;

        return [
            ['name' => 'Einladung zur Mitgliederversammlung', 'description' => 'Einladung mit Termin, Ort, Link und Antragsfrist.', 'category' => 'versammlung', 'audience' => 'members', 'subject' => 'Einladung: {versammlung} am {termin}',
                'body' => '<p>Guten Tag {vorname} {nachname},</p><p>wir laden Sie herzlich zur <strong>{versammlung}</strong> ein.</p><p><strong>Termin:</strong> {termin}<br><strong>Ort:</strong> {ort|wird noch bekannt gegeben}</p><p>Unterlagen und Online-Teilnahme: <a href="{link}">{link}</a></p><p>Anträge zur Tagesordnung können Sie bis zum {frist|Ende der Frist laut Satzung} einreichen.</p>'.$greet],
            ['name' => 'Erinnerung zur Mitgliederversammlung', 'description' => 'Erinnerung kurz vor dem Termin.', 'category' => 'versammlung', 'audience' => 'members', 'subject' => 'Erinnerung: {versammlung} in {tage_bis} Tagen',
                'body' => '<p>Guten Tag {vorname} {nachname},</p><p>wir erinnern Sie an die <strong>{versammlung}</strong> am {termin}.</p><p><strong>Ort:</strong> {ort|wird noch bekannt gegeben}<br><a href="{link}">{link}</a></p><p>Wir freuen uns auf Ihre Teilnahme.</p>'.$greet],
            ['name' => 'Absage oder Verlegung eines Termins', 'description' => 'Neutral formuliert für alle Gruppen.', 'category' => 'allgemein', 'audience' => 'all', 'subject' => 'Änderung: {versammlung}',
                'body' => '<p>Guten Tag {vorname} {nachname},</p><p>leider müssen wir den Termin <strong>{versammlung}</strong> ändern. Der neue Termin ist: {termin|noch offen}.</p><p>Ort: {ort|wird noch bekannt gegeben}</p><p>Wir bitten um Entschuldigung für die Unannehmlichkeiten.</p>'.$greet],
            ['name' => 'Willkommen im Verein', 'description' => 'Begrüßung neuer Mitglieder.', 'category' => 'versammlung', 'audience' => 'members', 'subject' => 'Willkommen im {verein}',
                'body' => '<p>Guten Tag {vorname} {nachname},</p><p>herzlich willkommen im {verein}. Wir freuen uns, dass Sie dabei sind.</p><p>Im Portal ({portal}) finden Sie Ihre Angaben, Mitteilungen und Termine.</p>'.$greet],
            ['name' => 'Seminar-Bestätigung an die Schule', 'description' => 'Bestätigt Termin und Ort eines Seminars.', 'category' => 'schulen', 'audience' => 'schools', 'subject' => 'Bestätigung: {versammlung}',
                'body' => '<p>Guten Tag {vorname} {nachname},</p><p>wir bestätigen Ihr Seminar <strong>{versammlung}</strong>.</p><p><strong>Termin:</strong> {termin}<br><strong>Ort:</strong> {ort|wird noch bekannt gegeben}</p><p>Bei Fragen antworten Sie einfach auf diese E-Mail.</p>'.$greet],
            ['name' => 'Erinnerung an die Schule', 'description' => 'Bitte um Anmeldung oder Rückmeldung bis zu einer Frist.', 'category' => 'schulen', 'audience' => 'schools', 'subject' => 'Rückmeldung bis {frist|bald}: {versammlung}',
                'body' => '<p>Guten Tag {vorname} {nachname},</p><p>wir bitten Sie um Ihre Rückmeldung zu <strong>{versammlung}</strong> bis zum {frist|bald}.</p><p>Das geht im Portal: <a href="{portal}">{portal}</a></p>'.$greet],
            ['name' => 'Team-Ausschreibung', 'description' => 'Aufruf an Teamende, sich auf Seminare zu bewerben.', 'category' => 'team', 'audience' => 'team', 'subject' => 'Neue Seminare: Bewerbung bis {stichtag|bald}',
                'body' => '<p>Hallo {vorname},</p><p>es gibt neue Seminare, für die wir Teamende suchen. Sie können sich bis zum {stichtag|bald} bewerben:</p><p><a href="{bewerbungslink}">{bewerbungslink}</a></p>'.$greet],
            ['name' => 'Team-Bestätigung', 'description' => 'Bestätigt die Mitarbeit an einem Seminar.', 'category' => 'team', 'audience' => 'team', 'subject' => 'Bestätigung: {versammlung}',
                'body' => '<p>Hallo {vorname},</p><p>vielen Dank für Ihre Bewerbung. Wir bestätigen Ihre Mitarbeit bei <strong>{versammlung}</strong>.</p><p><strong>Termin:</strong> {termin}<br><strong>Ort:</strong> {ort|wird noch bekannt gegeben}</p><p>Alle Einzelheiten finden Sie im Portal: <a href="{bewerbungslink}">{bewerbungslink}</a></p>'.$greet],
            ['name' => 'Absage an Bewerbende', 'description' => 'Freundliche Absage mit Verweis auf spätere Seminare.', 'category' => 'team', 'audience' => 'team', 'subject' => 'Ihre Bewerbung: {versammlung}',
                'body' => '<p>Hallo {vorname},</p><p>vielen Dank für Ihr Interesse an <strong>{versammlung}</strong>. Leider können wir Sie diesmal nicht berücksichtigen.</p><p>Wir würden uns freuen, wenn Sie sich auf spätere Seminare bewerben: <a href="{bewerbungslink}">{bewerbungslink}</a></p>'.$greet],
            ['name' => 'Feedback-Bitte an Lehrkräfte', 'description' => 'Bitte um Rückmeldung zu einem Seminar (Link je Seminar über „Seminare“).', 'category' => 'feedback', 'audience' => 'schools', 'subject' => 'Ihre Rückmeldung zu {versammlung}',
                'body' => '<p>Guten Tag {vorname} {nachname},</p><p>vielen Dank für Ihre Teilnahme an <strong>{versammlung}</strong>. Ihre Rückmeldung hilft uns, die Seminare zu verbessern. Sie dauert nur wenige Minuten und ist anonym.</p><p>Den Link zum Feedbackbogen finden Sie auf der Seite des Seminars im Portal.</p>'.$greet],
            ['name' => 'Feedback-Bitte an das Team', 'description' => 'Bitte um Rückmeldung zu einem Seminar.', 'category' => 'feedback', 'audience' => 'team', 'subject' => 'Ihre Rückmeldung zu {versammlung}',
                'body' => '<p>Hallo {vorname},</p><p>vielen Dank für Ihren Einsatz bei <strong>{versammlung}</strong>. Bitte geben Sie uns eine kurze, anonyme Rückmeldung. Den Link finden Sie auf der Seite des Seminars im Portal.</p>'.$greet],
            ['name' => 'Einarbeitung: Erinnerung', 'description' => 'Erinnerung an offene Einarbeitung.', 'category' => 'team', 'audience' => 'team', 'subject' => 'Erinnerung: Ihre Einarbeitung',
                'body' => '<p>Hallo {vorname},</p><p>Ihre Einarbeitung ist noch nicht abgeschlossen. Bitte erledigen Sie die offenen Schritte im Portal: <a href="{portal}">{portal}</a></p><p>Bei Fragen helfen wir gern.</p>'.$greet],
            ['name' => 'Rundmail der Geschäftsstelle', 'description' => 'Neutrale Vorlage für allgemeine Mitteilungen.', 'category' => 'allgemein', 'audience' => 'all', 'subject' => 'Mitteilung des {verein}',
                'body' => '<p>Guten Tag {vorname} {nachname},</p><p>hier steht Ihr Text.</p>'.$greet],
            ['name' => 'Einladung zu einer Großveranstaltung', 'description' => 'Einladung an Schulen und Lehrkräfte.', 'category' => 'schulen', 'audience' => 'schools', 'subject' => 'Einladung: {versammlung}',
                'body' => '<p>Guten Tag {vorname} {nachname},</p><p>wir laden Sie und Ihre Klassen zu <strong>{versammlung}</strong> ein.</p><p><strong>Termin:</strong> {termin}<br><strong>Ort:</strong> {ort|wird noch bekannt gegeben}</p><p>Anmeldung bis {frist|bald}: <a href="{link}">{link}</a></p>'.$greet],
            ['name' => 'Hinweis zur Datenänderung', 'description' => 'Bitte, Adresse und Kontaktdaten zu prüfen.', 'category' => 'versammlung', 'audience' => 'members', 'subject' => 'Bitte prüfen Sie Ihre Angaben',
                'body' => '<p>Guten Tag {vorname} {nachname},</p><p>bitte prüfen Sie, ob Ihre Adresse und Ihre Kontaktdaten noch stimmen. Sie können sie im Portal selbst ändern: <a href="{portal}">{portal}</a></p>'.$greet],
        ];
    }

    /** @return list<array{name: string, category: string, body: string}> */
    public static function snippets(): array
    {
        return [
            ['name' => 'Förmliche Anrede', 'category' => 'anrede', 'body' => '<p>Sehr geehrte Damen und Herren,</p>'],
            ['name' => 'Kollegiale Anrede (Teamende)', 'category' => 'anrede', 'body' => '<p>Hallo {vorname},</p>'],
            ['name' => 'Gruß mit Absender', 'category' => 'anrede', 'body' => '<p>Herzliche Grüße<br>{absender}</p>'],
            ['name' => 'Gruß Geschäftsstelle', 'category' => 'anrede', 'body' => '<p>Mit freundlichen Grüßen<br>Ihre Geschäftsstelle<br>{verein}</p>'],
            ['name' => 'Gruß Vorstand', 'category' => 'anrede', 'body' => '<p>Mit freundlichen Grüßen<br>Der Vorstand<br>{verein}</p>'],
            ['name' => 'Terminblock', 'category' => 'termine', 'body' => '<p><strong>Termin:</strong> {termin}<br><strong>Ort:</strong> {ort|wird noch bekannt gegeben}</p>'],
            ['name' => 'Online-Teilnahme', 'category' => 'termine', 'body' => '<p>Sie können online teilnehmen: <a href="{link}">{link}</a></p>'],
            ['name' => 'Anreise und Barrierefreiheit', 'category' => 'termine', 'body' => '<p>Bitte planen Sie Ihre Anreise rechtzeitig ein. Wenn Sie Unterstützung benötigen, zum Beispiel wegen eines barrierefreien Zugangs, melden Sie sich bitte vorab bei uns.</p>'],
            ['name' => 'Terminverschiebung', 'category' => 'termine', 'body' => '<p>Der Termin wurde verschoben. Der neue Termin ist: {termin|noch offen}. Wir bitten um Ihr Verständnis.</p>'],
            ['name' => 'Anmeldehinweis', 'category' => 'fristen', 'body' => '<p>Bitte melden Sie sich bis zum {frist|bald} an.</p>'],
            ['name' => 'Antragsfrist laut Satzung', 'category' => 'fristen', 'body' => '<p>Anträge zur Tagesordnung können Sie bis zum {frist|Ende der Frist laut Satzung} einreichen.</p>'],
            ['name' => 'Rückmeldung bis', 'category' => 'fristen', 'body' => '<p>Wir bitten um Ihre Rückmeldung bis zum {frist|bald}.</p>'],
            ['name' => 'Datenschutzhinweis (kurz)', 'category' => 'datenschutz', 'body' => '<p>Wir verarbeiten Ihre Daten nur, soweit es für die Arbeit des Vereins nötig ist. Mehr dazu finden Sie in der Datenschutzerklärung im Portal.</p>'],
            ['name' => 'Abmeldehinweis', 'category' => 'datenschutz', 'body' => '<p>Sie möchten diese Mitteilungen nicht mehr erhalten? Hier können Sie sich abmelden: {abmelden|Diese Mitteilung ist nicht abbestellbar.}</p>'],
            ['name' => 'Kontakt bei Fragen', 'category' => 'datenschutz', 'body' => '<p>Bei Fragen antworten Sie einfach auf diese E-Mail.</p>'],
            ['name' => 'Kontaktblock Geschäftsstelle', 'category' => 'kontakt', 'body' => '<p>Ihre Geschäftsstelle<br>{verein}<br>E-Mail: Antwort auf diese Nachricht</p>'],
            ['name' => 'Kontaktblock Koordination', 'category' => 'kontakt', 'body' => '<p>Ihre Koordination<br>{verein}<br>Bei Fragen antworten Sie bitte auf diese Nachricht.</p>'],
            ['name' => 'Kontaktblock Vorstand', 'category' => 'kontakt', 'body' => '<p>Der Vorstand<br>{verein}<br>Bei Fragen antworten Sie bitte auf diese Nachricht.</p>'],
            ['name' => 'Ablaufhinweis für Schulen', 'category' => 'seminare', 'body' => '<p>Bitte sorgen Sie dafür, dass ein Raum und die technische Ausstattung bereitstehen und die Klassen pünktlich anwesend sind. Eine Lehrkraft sollte während des Seminars erreichbar sein.</p>'],
            ['name' => 'Hinweis für Teamende (Treffpunkt und Material)', 'category' => 'seminare', 'body' => '<p>Bitte seien Sie rechtzeitig vor Beginn am Treffpunkt. Das Material finden Sie im Portal auf der Seite des Seminars.</p>'],
            ['name' => 'Hinweis zum Kostenvoranschlag', 'category' => 'seminare', 'body' => '<p>Den Kostenvoranschlag erhalten Sie vor dem Seminar. Er ist bis zum angegebenen Datum gültig.</p>'],
            ['name' => 'Hinweis auf den Feedbackbogen', 'category' => 'seminare', 'body' => '<p>Nach dem Seminar bitten wir um eine kurze, anonyme Rückmeldung. Den Link finden Sie auf der Seite des Seminars im Portal.</p>'],
            ['name' => 'Hinweis zur Kostenerstattung', 'category' => 'seminare', 'body' => '<p>Auslagen können Sie im Portal als Antrag einreichen: <a href="{antraglink}">{antraglink}</a>. Bitte reichen Sie die Belege mit ein.</p>'],
            ['name' => 'Danke an Ehrenamtliche', 'category' => 'allgemein', 'body' => '<p>Vielen Dank für Ihr ehrenamtliches Engagement. Ohne Sie wäre unsere Arbeit nicht möglich.</p>'],
            ['name' => 'Keine Fotos von Teilnehmenden', 'category' => 'allgemein', 'body' => '<p>Bitte machen Sie keine Fotos von Teilnehmenden und veröffentlichen Sie keine Bilder ohne ausdrückliche Einwilligung.</p>'],
            ['name' => 'Hinweis zur Barrierefreiheit', 'category' => 'allgemein', 'body' => '<p>Wenn Sie für die Teilnahme Unterstützung benötigen, sagen Sie uns bitte rechtzeitig Bescheid. Wir sorgen dafür, dass alle teilnehmen können.</p>'],
        ];
    }
}
