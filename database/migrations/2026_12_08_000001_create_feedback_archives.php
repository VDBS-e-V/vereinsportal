<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_links', function (Blueprint $table): void {
            $table->timestamp('anonymized_at')->nullable()->after('closed_at');
        });

        // Archiv: nur Zahlen je Link (keine Freitexte, keine Namen), damit Auswertungen nach dem Löschen der Einzelantworten möglich bleiben.
        Schema::create('feedback_archives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('link_id')->unique()->constrained('feedback_links')->cascadeOnDelete();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->string('audience', 20);
            $table->unsignedInteger('responses_count');
            $table->decimal('overall_avg', 4, 2)->nullable();
            $table->json('summary');
            $table->timestamp('archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_archives');
        Schema::table('feedback_links', function (Blueprint $table): void {
            $table->dropColumn('anonymized_at');
        });
    }
};
