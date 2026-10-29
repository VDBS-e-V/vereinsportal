<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_product_texts', function (Blueprint $table): void {
            $table->id();
            $table->string('product', 32)->unique();
            $table->string('title', 150);
            $table->text('content');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Standardtexte mit Platzhaltern (siehe QuoteTexts::PLACEHOLDERS)
        $texts = [
            'sv_days' => ['SV-Tage ({stueckzahl})', "Je {teamende} Teamer:innen pro Tag\nModerationsmaterial\nRaum und ggf. Verpflegung werden durch die Schule gestellt"],
            'coaching' => ['SV-Coaching ({stueckzahl} {einheit})', "Je {teamende} Teamer:innen\nUnterstützung von Planung, Organisation und Absprache"],
            'election' => ['SV-Wahl', "Je {teamende} Teamer:innen\nBegleitung und Durchführung der Wahl\nModerationsmaterial"],
            'gsv_support' => ['Begleitung GSV-Sitzungen ({stueckzahl} {einheit})', 'Unterstützung der Planung und Organisation, sowie Unterstützung vor Ort'],
        ];

        foreach ($texts as $product => [$title, $content]) {
            DB::table('seminar_product_texts')->insert(['product' => $product, 'title' => $title, 'content' => $content, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_product_texts');
    }
};
