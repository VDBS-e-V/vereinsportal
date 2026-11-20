<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_topics', function (Blueprint $table): void {
            $table->boolean('in_faq_page')->default(true)->after('faq_offer_form'); // Fragen auch auf der großen FAQ-Seite (/faq) zeigen
        });

        $this->seedFaq();
    }

    public function down(): void
    {
        Schema::table('contact_topics', function (Blueprint $table): void {
            $table->dropColumn('in_faq_page');
        });
    }

    /** Entwurf der großen FAQ: bisherige Fragen von „Über das Portal“ und weitere zu den geplanten Themen. Zum Korrigieren im Portal gedacht (Kontaktformular › Themen und FAQ). */
    private function seedFaq(): void
    {
        $now = now();
        DB::table('contact_topics')->where('action', 'category')->where('title', 'Sonstiges')->update(['sort_order' => 90]);
        $support = DB::table('contact_forms')->where('slug', 'portal-support')->value('id');
        $board = DB::table('contact_forms')->where('slug', 'vorstand')->value('id');

        $category = fn (string $title, string $description, int $sort): int => DB::table('contact_topics')->where('action', 'category')->where('title', $title)->value('id')
            ?? DB::table('contact_topics')->insertGetId(['parent_id' => null, 'title' => $title, 'description' => $description, 'keywords' => null, 'action' => 'category', 'contact_form_id' => null, 'target_url' => null, 'faq_offer_form' => true, 'in_faq_page' => true, 'sort_order' => $sort, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);

        $faqTopic = fn (int $parent, string $title, string $description, string $keywords, ?int $form, int $sort): int => DB::table('contact_topics')->insertGetId(['parent_id' => $parent, 'title' => $title, 'description' => $description, 'keywords' => $keywords, 'action' => 'faq', 'contact_form_id' => $form, 'target_url' => null, 'faq_offer_form' => true, 'in_faq_page' => true, 'sort_order' => $sort, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);

        $entries = function (int $topic, array $rows): void {
            $now = now();

            foreach ($rows as $index => [$question, $answer]) {
                DB::table('contact_faqs')->insert(['contact_topic_id' => $topic, 'question' => $question, 'answer' => $answer, 'sort_order' => ($index + 1) * 10, 'created_at' => $now, 'updated_at' => $now]);
            }
        };

        $account = (int) DB::table('contact_topics')->where('action', 'category')->where('title', 'Zugang und Konto')->value('id');
        $association = (int) DB::table('contact_topics')->where('action', 'category')->where('title', 'Verein und Mitgliedschaft')->value('id');
        $login = (int) DB::table('contact_topics')->where('action', 'faq')->where('title', 'Passwort vergessen oder Anmeldung klappt nicht')->value('id');

        // Weitere Fragen zum vorhandenen Thema „Passwort vergessen oder Anmeldung klappt nicht“
        $entries($login, [
            ['Die Anmeldung wird nach mehreren Versuchen gesperrt.', 'Nach mehreren Fehlversuchen schützt das Portal Ihr Konto und sperrt die Anmeldung für kurze Zeit. Warten Sie einige Minuten und versuchen Sie es dann erneut. Wenn Sie Ihr Passwort nicht mehr kennen, fordern Sie über [Passwort vergessen](/passwort-vergessen) einen neuen Link an.'],
            ['Ich habe das Gerät mit meiner Authenticator-App verloren.', 'Melden Sie sich mit einem Ihrer **Wiederherstellungscodes** an, die Sie bei der Einrichtung erhalten haben. Danach können Sie unter Konto › Sicherheit die Zwei-Faktor-Anmeldung neu einrichten. Haben Sie auch keine Codes mehr, schreiben Sie uns über den Knopf unten.'],
        ]);

        // Bereich „Allgemeines zum Portal“: die bisherigen Fragen von „Über das Portal“
        $general = $category('Allgemeines zum Portal', 'Was das Vereinsportal ist und wer es nutzen kann', 5);
        $overview = $faqTopic($general, 'Das Vereinsportal im Überblick', 'Wofür das Portal da ist und wer es nutzt', 'portal überblick vorteile nutzen', $support, 10);
        $entries($overview, [
            ['Was ist das Vereinsportal und wofür wurde es entwickelt?', 'Das Vereinsportal ist Ihre zentrale Online-Anlaufstelle für alle Belange rund um unseren Verein. Es wurde entwickelt, um die Kommunikation, den Informationsaustausch und die Organisation für Teamer:innen, Vereinsmitglieder, die Verwaltung und Schulen zu vereinfachen und zu optimieren.'],
            ['Welche Themenbereiche deckt der Verein ab, die im Portal relevant sind?', 'Unser Verein ist in den Bereichen **SV-Arbeit, Medienkompetenz, Schulbibliothek** und **Demokratiebildung** aktiv. Alle Informationen, Materialien und Tools zu diesen Schwerpunkten finden Sie gebündelt im Portal.'],
            ['Wer kann das Vereinsportal nutzen?', "Das Portal richtet sich an alle, die mit unserem Verein in Verbindung stehen:\n\n- **Teamer:innen:** für Workshop-Materialien, Planung und Austausch.\n- **Vereinsmitglieder:** für aktuelle Infos, Termine und zur aktiven Teilnahme.\n- **Verwaltung:** für interne Organisation, Mitglieder- und Ressourcenmanagement.\n- **Schulen (Schüler:innen, Lehrkräfte usw.):** für Infos zu Workshops, Anfragen und Lehrmaterialien."],
            ['Welche Vorteile bietet mir das Portal?', "Das Portal bietet Ihnen zahlreiche Vorteile, darunter:\n\n- **Zentraler Informationszugang:** alle wichtigen Dokumente und Ansprechpartner an einem Ort.\n- **Effiziente Kommunikation:** vereinfachter Austausch zwischen allen Beteiligten.\n- **Umfassende Ressourcen:** leichter Zugriff auf Workshop-Materialien, Leitfäden und Best Practices.\n- **Transparenz:** immer auf dem neuesten Stand über Vereinsaktivitäten und Projekte.\n- **Vereinfachte Prozesse:** von der Workshop-Anmeldung bis zur internen Verwaltung, alles digital und unkompliziert."],
            ['Wie erhalte ich Zugang zum Vereinsportal?', 'Informationen zum Zugang (z. B. Registrierung, Login-Daten) erhalten Sie von der Vereinsverwaltung oder direkt über die Anmeldeoptionen auf der Seite [Zugang zum Portal](/zugang-zum-portal). Ihr Passwort vergessen? Fordern Sie über [Passwort vergessen](/passwort-vergessen) einen Link an.'],
            ['Was ist die Zwei-Faktor-Anmeldung?', 'Neben dem Passwort geben Sie bei der Anmeldung einen Code ein, entweder aus einer Authenticator-App oder per E-Mail. Das schützt Ihr Konto auch dann, wenn jemand Ihr Passwort kennt. Einrichten können Sie sie unter Konto › Sicherheit.'],
            ['Wo finde ich Hilfe, wenn ich Probleme mit dem Portal habe?', 'Sollten Sie technische Schwierigkeiten haben oder Fragen zur Nutzung des Portals aufkommen, wenden Sie sich bitte über das [Kontaktformular](/kontakt) an den Support.'],
        ]);

        // Zugang und Konto
        $accountTopic = $faqTopic($account, 'Konto, E-Mail-Adresse und Daten', 'Eigene Angaben ändern, Sicherheit, Konto löschen', 'konto email passwort zwei-faktor löschen sicherheit profil', $support, 30);
        $entries($accountTopic, [
            ['Wie ändere ich meine E-Mail-Adresse?', 'Tragen Sie die neue Adresse in Ihrem Konto ein. Wir senden an die **neue** Adresse einen Bestätigungslink; die Änderung gilt erst, wenn Sie ihn öffnen. An die bisherige Adresse geht eine Benachrichtigung, falls die Änderung nicht von Ihnen stammt.'],
            ['Wie ändere ich mein Passwort?', 'Melden Sie sich an und öffnen Sie Konto › Sicherheit. Dort legen Sie ein neues Passwort fest und können alle anderen Sitzungen beenden.'],
            ['Wie richte ich die Zwei-Faktor-Anmeldung ein?', 'Unter Konto › Sicherheit wählen Sie, ob Sie den Code aus einer Authenticator-App oder per E-Mail erhalten möchten. Bewahren Sie die angezeigten Wiederherstellungscodes sicher auf.'],
            ['Wie kann ich mein Konto löschen?', 'Sie können die Löschung in Ihrem Konto beantragen. Wir bestätigen den Antrag per E-Mail; solange die Löschung noch nicht ausgeführt ist, lässt sie sich über den Link in dieser E-Mail wieder abbrechen. Bei einer Löschung werden Ihre Angaben anonymisiert.'],
        ]);

        $accessTopic = $faqTopic($account, 'Zugang beantragen', 'Wer Zugang erhält und was danach passiert', 'zugang antrag registrieren geschäftsstelle', $support, 40);
        $entries($accessTopic, [
            ['Wer kann Zugang zum Portal beantragen?', 'Zugang erhalten Personen, die mit dem Verein zusammenarbeiten, zum Beispiel Mitglieder, Teamende, Mitarbeitende und Schulen. Beantragen können Sie ihn auf der Seite [Zugang zum Portal](/zugang-zum-portal).'],
            ['Wie geht es nach dem Antrag weiter?', 'Sie erhalten eine Bestätigung per E-Mail. Die Geschäftsstelle prüft Ihren Antrag und meldet sich bei Ihnen. Bei einer Zusage bekommen Sie einen Link, mit dem Sie Ihr Konto einrichten.'],
            ['Ich habe nach dem Antrag keine E-Mail erhalten.', 'Bitte prüfen Sie den Spam-Ordner und ob Sie die Adresse richtig geschrieben haben. Finden Sie die E-Mail dort nicht, schreiben Sie uns über den Knopf unten.'],
            ['Was bedeutet „Rolle“ im Portal?', 'Ihre Rolle (zum Beispiel Mitglied, Teamende:r oder Schule) bestimmt, welche Bereiche und Funktionen Sie sehen. Sie wird von der Geschäftsstelle oder dem Vorstand vergeben.'],
        ]);

        // Verein und Mitgliedschaft
        $membership = $faqTopic($association, 'Mitgliedschaft', 'Mitglied werden, Daten ändern, Austritt', 'mitglied mitgliedschaft beitrag austritt kündigen adresse', $board, 30);
        $entries($membership, [
            ['Wie werde ich Mitglied?', 'Stellen Sie auf der Seite [Mitglied werden](/mitglied-werden) Ihren Antrag. Sie erhalten eine E-Mail mit einem Bestätigungslink. Über die Aufnahme entscheidet der Vorstand; Sie hören danach von uns.'],
            ['Wie ändere ich meine Daten oder beende meine Mitgliedschaft?', 'Bitte schreiben Sie dem Vorstand über das Kontaktformular. Nennen Sie, was sich ändern soll, damit wir es dem richtigen Datensatz zuordnen können.'],
            ['Was kann ich als Mitglied im Portal tun?', 'Als Mitglied sehen Sie den Bereich **Mitglieder** mit Informationen und Dokumenten des Vereins sowie Ihre Mitgliedschaft.'],
        ]);

        // Schulen und Workshops
        $schools = $category('Schulen und Workshops', 'Angebote für Schulen, Anfragen und Feedback', 40);
        $workshops = $faqTopic($schools, 'Workshops und Seminare für Schulen', 'Anfragen, Ablauf und Rückmeldung', 'schule workshop seminar anfrage lehrkraft feedback', null, 10);
        $entries($workshops, [
            ['Wer kann einen Workshop oder ein Seminar anfragen?', 'Schulen, Schülervertretungen und Lehrkräfte können Angebote zu **SV-Arbeit, Medienkompetenz, Schulbibliothek** und **Demokratiebildung** anfragen.'],
            ['Wie frage ich ein Angebot an?', 'Schreiben Sie uns über das [Kontaktformular](/kontakt) und nennen Sie Ihre Schule, das gewünschte Themenfeld und einen möglichen Zeitraum. Die Koordination meldet sich bei Ihnen.'],
            ['Wie gebe ich nach einer Veranstaltung Rückmeldung?', 'Zu vielen Veranstaltungen gibt es einen Link oder QR-Code für ein kurzes, anonymes Feedback. Wir freuen uns über jede Rückmeldung.'],
        ]);

        // Mitmachen im Team
        $team = $category('Mitmachen im Team', 'Teamende, Einarbeitung, Verträge und Abrechnung', 50);
        $teamTopic = $faqTopic($team, 'Teamarbeit, Verträge und Abrechnung', 'Mitmachen, Einarbeitung, Honorarvertrag, Auslagen', 'team teamer teamerin einarbeitung honorar vertrag abrechnung fahrtkosten auslagen', null, 10);
        $entries($teamTopic, [
            ['Wie kann ich Teamer:in werden?', 'Schreiben Sie uns über das [Kontaktformular](/kontakt), dass Sie mitmachen möchten, und nennen Sie kurz Ihre Region und Erfahrung. Die Koordination meldet sich bei Ihnen.'],
            ['Wie läuft die Einarbeitung?', 'Nach Ihrer Aufnahme führt Sie das Portal durch die Schritte der Einarbeitung. Offene Schritte sehen Sie in Ihrem Konto; bei Fragen hilft die Koordination.'],
            ['Wie komme ich zu meinem Honorarvertrag?', 'Den Vertrag bereitet die Geschäftsstelle im Portal vor. Sie erhalten ihn zur Ansicht, unterschreiben ihn und geben ihn wie beschrieben zurück.'],
            ['Wie rechne ich Fahrtkosten und Auslagen ab?', 'Wenn Ihre Rolle es vorsieht, reichen Sie Fahrtkosten, Auslagen und Materialbestellungen im Portal als Antrag ein. Sie sehen den Stand und erhalten eine Rückmeldung.'],
        ]);

        // Datenschutz und Rechtliches
        $legal = $category('Datenschutz und Rechtliches', 'Ihre Daten, Auskunft und Barrierefreiheit', 60);
        $privacy = $faqTopic($legal, 'Datenschutz und Barrierefreiheit', 'Welche Daten wir verarbeiten und welche Rechte Sie haben', 'datenschutz dsgvo auskunft löschung barrierefreiheit impressum', null, 10);
        $entries($privacy, [
            ['Welche Daten speichert das Portal über mich?', 'Das Portal speichert die Angaben, die für Ihr Konto und Ihre Rolle nötig sind. Einzelheiten stehen in der [Datenschutzerklärung](/datenschutz).'],
            ['Wird meine Nachricht aus dem Kontaktformular gespeichert?', 'Nein. Das Kontaktformular sendet Ihre Angaben per E-Mail an die zuständige Stelle; im Portal selbst wird nichts gespeichert.'],
            ['Wie erhalte ich Auskunft über meine Daten oder lasse sie löschen?', 'Schreiben Sie uns über das [Kontaktformular](/kontakt) oder an die Adresse in der Datenschutzerklärung. Wir prüfen Ihre Anfrage und melden uns bei Ihnen.'],
            ['Wo finde ich Impressum und Erklärung zur Barrierefreiheit?', 'Beides finden Sie in der Fußzeile: [Impressum](/impressum) und [Barrierefreiheit](/barrierefreiheit). Hindernisse melden Sie uns gern über das Kontaktformular.'],
        ]);
    }
};
