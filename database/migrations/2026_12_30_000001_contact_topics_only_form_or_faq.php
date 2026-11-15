<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unter /kontakt gibt es keine Infoseiten mehr: Ein Thema führt entweder zu einem Kontaktformular oder zur FAQ-Seite (/faq).
 * Themen der Art „Link“ werden FAQ-Themen (die Antworten enthalten die Links), die FAQ-Seite zeigt alle sichtbaren Kategorien.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bekannte Link-Themen auf passende FAQ-Kategorien umstellen; andere werden zum Standardformular
        $map = ['Ich möchte Zugang zum Portal' => 'Zugang beantragen', 'Mitglied werden' => 'Mitgliedschaft'];

        foreach (DB::table('contact_topics')->where('action', 'link')->get(['id', 'title']) as $topic) {
            $category = isset($map[$topic->title]) ? DB::table('faq_categories')->where('title', $map[$topic->title])->value('id') : null;

            DB::table('contact_topics')->where('id', $topic->id)->update($category !== null
                ? ['action' => 'faq', 'faq_category_id' => $category]
                : ['action' => 'default', 'faq_category_id' => null]);
        }

        // FAQ-Themen leiten nur noch zur FAQ-Seite: kein eigenes Formular, keine Rückfrage-Schaltung
        DB::table('contact_topics')->where('action', 'faq')->update(['contact_form_id' => null]);

        Schema::table('contact_topics', function (Blueprint $table): void {
            $table->dropColumn(['target_url', 'faq_offer_form']);
        });

        // Alle sichtbaren Kategorien stehen auf der FAQ-Seite, auf die die Themen verweisen
        Schema::table('faq_categories', function (Blueprint $table): void {
            $table->dropColumn('in_faq_page');
        });
    }

    public function down(): void
    {
        Schema::table('faq_categories', function (Blueprint $table): void {
            $table->boolean('in_faq_page')->default(true)->after('sort_order');
        });

        Schema::table('contact_topics', function (Blueprint $table): void {
            $table->string('target_url', 500)->nullable()->after('faq_category_id');
            $table->boolean('faq_offer_form')->default(true)->after('target_url');
        });
    }
};
