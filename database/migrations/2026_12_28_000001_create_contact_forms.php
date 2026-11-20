<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_forms', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('kind', 10)->default('message'); // message = Nachricht mit Betreff und Text, data = nur Angaben
            $table->string('recipient_email', 254);
            $table->string('recipient_label', 120);
            $table->string('ask_phone', 10)->default('no'); // no | optional | required
            $table->string('ask_organization', 10)->default('no');
            $table->json('fields')->nullable();
            $table->text('success_text')->nullable();
            $table->boolean('send_copy')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_topics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('contact_topics')->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('description', 250)->nullable();
            $table->text('keywords')->nullable();
            $table->string('action', 10)->default('form'); // category | form | faq | default | link
            $table->foreignId('contact_form_id')->nullable()->constrained('contact_forms')->nullOnDelete();
            $table->string('target_url', 500)->nullable();
            $table->boolean('faq_offer_form')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_topic_id')->constrained('contact_topics')->cascadeOnDelete();
            $table->string('question', 250);
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_faqs');
        Schema::dropIfExists('contact_topics');
        Schema::dropIfExists('contact_forms');
    }

    /** Ausgangsbestand: die bisherigen Empfänger des Kontaktformulars als Formulare, dazu Beispielthemen. Alles lässt sich im Portal ändern. */
    private function seedDefaults(): void
    {
        $now = now();
        $recipients = (array) config('portal.contact_form.recipients', []);
        $generalEmail = (string) ($recipients['general']['email'] ?? 'kontakt@vdb.schule');
        $board = (string) ($recipients['board']['email'] ?? 'vorstand@vdb.schule');
        $support = (string) ($recipients['support']['email'] ?? 'support@portal.vdb.schule');

        $form = fn (array $values): int => DB::table('contact_forms')->insertGetId($values + ['kind' => 'message', 'ask_phone' => 'no', 'ask_organization' => 'no', 'send_copy' => false, 'is_default' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);

        $form(['slug' => 'allgemeine-anfrage', 'name' => 'Allgemeine Anfrage', 'recipient_email' => $generalEmail, 'recipient_label' => 'Allgemeine Anfrage', 'is_default' => true, 'description' => 'Für alle Anliegen, die zu keinem anderen Thema passen.']);
        $boardId = $form(['slug' => 'vorstand', 'name' => 'Vorstand', 'recipient_email' => $board, 'recipient_label' => 'Vorstand']);
        $supportId = $form(['slug' => 'portal-support', 'name' => 'Portal-Support', 'recipient_email' => $support, 'recipient_label' => 'Portal-Support', 'ask_organization' => 'optional']);
        $callback = $form([
            'slug' => 'rueckruf', 'name' => 'Rückruf erbeten', 'kind' => 'data', 'recipient_email' => $generalEmail, 'recipient_label' => 'Rückruf',
            'ask_phone' => 'required',
            'description' => 'Hinterlassen Sie Ihre Telefonnummer, wir rufen Sie zurück.',
            'success_text' => 'Danke, wir rufen Sie zurück.',
            'fields' => json_encode([
                ['key' => 'anliegen', 'label' => 'Worum geht es kurz?', 'type' => 'text', 'required' => true, 'options' => [], 'hint' => null],
                ['key' => 'erreichbarkeit', 'label' => 'Wann sind Sie erreichbar?', 'type' => 'text', 'required' => false, 'options' => [], 'hint' => 'z. B. wochentags nach 15 Uhr'],
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $topic = fn (array $values): int => DB::table('contact_topics')->insertGetId($values + ['parent_id' => null, 'description' => null, 'keywords' => null, 'action' => 'form', 'contact_form_id' => null, 'target_url' => null, 'faq_offer_form' => true, 'sort_order' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);

        $account = $topic(['title' => 'Zugang und Konto', 'description' => 'Anmeldung, Passwort, Zugang beantragen', 'action' => 'category', 'sort_order' => 10]);
        $topic(['title' => 'Ich möchte Zugang zum Portal', 'parent_id' => $account, 'action' => 'link', 'target_url' => '/zugang-zum-portal', 'keywords' => 'zugang beantragen registrieren konto', 'sort_order' => 10]);
        $faq = $topic(['title' => 'Passwort vergessen oder Anmeldung klappt nicht', 'parent_id' => $account, 'action' => 'faq', 'contact_form_id' => $supportId, 'keywords' => 'passwort login anmelden zwei-faktor code', 'sort_order' => 20]);
        $topic(['title' => 'Technisches Problem im Portal', 'parent_id' => $account, 'action' => 'form', 'contact_form_id' => $supportId, 'keywords' => 'fehler störung problem', 'sort_order' => 30]);

        $association = $topic(['title' => 'Verein und Mitgliedschaft', 'description' => 'Mitglied werden, Fragen an den Vorstand', 'action' => 'category', 'sort_order' => 20]);
        $topic(['title' => 'Mitglied werden', 'parent_id' => $association, 'action' => 'link', 'target_url' => '/mitglied-werden', 'keywords' => 'mitgliedschaft antrag beitritt', 'sort_order' => 10]);
        $topic(['title' => 'Frage an den Vorstand', 'parent_id' => $association, 'action' => 'form', 'contact_form_id' => $boardId, 'keywords' => 'vorstand verein', 'sort_order' => 20]);

        $other = $topic(['title' => 'Sonstiges', 'description' => 'Rückruf oder ein anderes Anliegen', 'action' => 'category', 'sort_order' => 30]);
        $topic(['title' => 'Rückruf erbeten', 'parent_id' => $other, 'action' => 'form', 'contact_form_id' => $callback, 'keywords' => 'telefon anrufen rückruf', 'sort_order' => 10]);
        $topic(['title' => 'Mein Thema ist nicht dabei', 'parent_id' => $other, 'action' => 'default', 'keywords' => 'allgemein sonstiges anfrage', 'sort_order' => 20]);

        $entries = [
            ['Wie setze ich mein Passwort zurück?', 'Öffnen Sie die Anmeldeseite und wählen Sie „Passwort vergessen?“. Geben Sie Ihre E-Mail-Adresse ein, wir senden Ihnen einen Link zum Festlegen eines neuen Passworts.'],
            ['Der Link zum Zurücksetzen kommt nicht an.', 'Prüfen Sie bitte den Spam-Ordner und ob Sie die E-Mail-Adresse verwenden, mit der Ihr Konto angelegt wurde. Der Link ist nur kurze Zeit gültig; fordern Sie bei Bedarf einen neuen an.'],
            ['Der Zwei-Faktor-Code wird nicht akzeptiert.', 'Achten Sie darauf, dass die Uhr Ihres Geräts richtig geht, und geben Sie den aktuellen Code ein, der sich alle 30 Sekunden ändert. Mit einem Wiederherstellungscode können Sie sich ebenfalls anmelden.'],
        ];

        foreach ($entries as $index => [$question, $answer]) {
            DB::table('contact_faqs')->insert(['contact_topic_id' => $faq, 'question' => $question, 'answer' => $answer, 'sort_order' => ($index + 1) * 10, 'created_at' => $now, 'updated_at' => $now]);
        }
    }
};
