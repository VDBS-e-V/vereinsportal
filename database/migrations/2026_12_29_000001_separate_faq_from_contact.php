<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Trennt die FAQ vom Kontaktformular: eigene Kategorien und Fragen; Kontaktthemen verweisen nur noch auf eine FAQ-Kategorie. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 150);
            $table->string('description', 250)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('in_faq_page')->default(true); // auf der großen FAQ-Seite (/faq) zeigen
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('faq_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('faq_category_id')->constrained('faq_categories')->cascadeOnDelete();
            $table->string('question', 250);
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('contact_topics', function (Blueprint $table): void {
            $table->foreignId('faq_category_id')->nullable()->after('contact_form_id')->constrained('faq_categories')->nullOnDelete();
        });

        $this->moveExistingFaq();

        Schema::table('contact_topics', function (Blueprint $table): void {
            $table->dropColumn('in_faq_page');
        });

        Schema::dropIfExists('contact_faqs');
    }

    public function down(): void
    {
        Schema::create('contact_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_topic_id')->constrained('contact_topics')->cascadeOnDelete();
            $table->string('question', 250);
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('contact_topics', function (Blueprint $table): void {
            $table->boolean('in_faq_page')->default(true);
            $table->dropConstrainedForeignId('faq_category_id');
        });

        Schema::dropIfExists('faq_entries');
        Schema::dropIfExists('faq_categories');
    }

    /** Jedes bisherige FAQ-Thema wird zu einer FAQ-Kategorie; das Thema verweist darauf. */
    private function moveExistingFaq(): void
    {
        $topics = DB::table('contact_topics as t')
            ->leftJoin('contact_topics as p', 'p.id', '=', 't.parent_id')
            ->where('t.action', 'faq')
            ->orderByRaw('COALESCE(p.sort_order, 99999)')
            ->orderBy('t.sort_order')
            ->orderBy('t.title')
            ->get(['t.id', 't.title', 't.description', 't.is_active', 't.in_faq_page', 'p.is_active as parent_active']);

        foreach ($topics as $index => $topic) {
            $category = DB::table('faq_categories')->insertGetId([
                'title' => $topic->title,
                'description' => $topic->description,
                'sort_order' => ($index + 1) * 10,
                'in_faq_page' => (bool) $topic->in_faq_page,
                'is_active' => (bool) $topic->is_active && ($topic->parent_active === null || (bool) $topic->parent_active),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (DB::table('contact_faqs')->where('contact_topic_id', $topic->id)->orderBy('sort_order')->orderBy('id')->get() as $faq) {
                DB::table('faq_entries')->insert(['faq_category_id' => $category, 'question' => $faq->question, 'answer' => $faq->answer, 'sort_order' => $faq->sort_order, 'created_at' => now(), 'updated_at' => now()]);
            }

            DB::table('contact_topics')->where('id', $topic->id)->update(['faq_category_id' => $category]);
        }
    }
};
