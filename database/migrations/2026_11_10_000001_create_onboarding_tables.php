<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dokumente für das Onboarding (Satzung, Willkommensbrief, Erklärungen). Die jüngste Datei je Art gilt, ältere bleiben erhalten.
        Schema::create('onboarding_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 32);
            $table->string('original_name', 200);
            $table->string('path', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->char('sha256', 64);
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at');

            $table->index(['kind', 'id']);
        });

        // Einarbeitung der Teamenden: eine je Konto, mit einem Eintrag je Schritt
        Schema::create('team_onboardings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('team_onboarding_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('onboarding_id')->constrained('team_onboardings')->cascadeOnDelete();
            $table->string('step', 32);
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('by_self')->default(false);
            $table->foreignId('document_id')->nullable()->constrained('onboarding_documents')->nullOnDelete();
            $table->json('data')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['onboarding_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_onboarding_steps');
        Schema::dropIfExists('team_onboardings');
        Schema::dropIfExists('onboarding_documents');
    }
};
