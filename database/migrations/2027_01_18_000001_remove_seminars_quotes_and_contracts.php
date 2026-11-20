<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entfernt Seminare, Kostenvoranschläge, Verträge, Rückmeldungen (Feedback) und Briefvorlagen samt Bewerbungen der Teamenden aus der Datenbank.
 * Die Verknüpfungen in Anträgen und Rundschreiben zu Seminaren fallen weg. Rückgängig machen lässt sich das nur über die Sicherung.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const TABLES = [
        'feedback_archives', 'feedback_responses', 'feedback_links', 'feedback_form_versions', 'feedback_forms',
        'school_contracts', 'seminar_contracts', 'contractor_profiles', 'contract_templates', 'contract_settings', 'contract_counters',
        'letter_template_defaults', 'letter_templates',
        'seminar_print_plans', 'seminar_closures', 'seminar_documents', 'seminar_team_invites', 'seminar_applications', 'team_profiles',
        'seminar_events', 'seminar_sessions', 'seminar_quote_versions', 'seminar_quotes', 'seminar_price_lists', 'seminar_product_texts',
        'seminar_counters', 'seminars',
    ];

    public function up(): void
    {
        foreach (['form_applications', 'mailings'] as $table) {
            if (Schema::hasColumn($table, 'seminar_id')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    // Auch unter SQLite zuerst die Fremdschlüssel-Definition entfernen, sonst scheitert das Entfernen der Spalte.
                    $blueprint->dropForeign(['seminar_id']);
                    $blueprint->dropColumn('seminar_id');
                });
            }
        }

        Schema::disableForeignKeyConstraints();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Die Funktionen gibt es nicht mehr; die alten Migrationen bauen die Tabellen bei Bedarf neu auf.
    }
};
