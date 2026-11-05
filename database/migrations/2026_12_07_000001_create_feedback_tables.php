<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_forms', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('audience', 20)->index();
            $table->string('title', 200);
            $table->text('intro')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->json('questions');
            $table->boolean('active')->default(true);
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('feedback_form_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained('feedback_forms')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title', 200);
            $table->text('intro')->nullable();
            $table->json('questions');
            $table->timestamp('created_at')->nullable();

            $table->unique(['form_id', 'version']);
        });

        Schema::create('feedback_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->foreignId('form_id')->constrained('feedback_forms')->cascadeOnDelete();
            $table->unsignedInteger('form_version');
            $table->string('audience', 20);
            $table->string('token_hash', 64)->index();
            $table->text('token_enc');
            $table->string('label', 100)->nullable();
            $table->timestamp('opens_at');
            $table->timestamp('closes_at');
            $table->unsignedInteger('max_responses')->nullable();
            $table->unsignedTinyInteger('min_responses')->default(5);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['seminar_id', 'audience']);
        });

        // Antworten ohne Kennung: nur der Tag der Abgabe, keine IP, kein User-Agent, keine Sitzung.
        Schema::create('feedback_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('link_id')->constrained('feedback_links')->cascadeOnDelete();
            $table->date('submitted_on');
            $table->json('answers');
            $table->string('respondent_name', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_responses');
        Schema::dropIfExists('feedback_links');
        Schema::dropIfExists('feedback_form_versions');
        Schema::dropIfExists('feedback_forms');
    }
};
