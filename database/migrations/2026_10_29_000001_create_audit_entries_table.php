<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Modul: Audit.
     *
     * Unveränderliches Protokoll sicherheits- und verwaltungsrelevanter Ereignisse. Akteur und Betroffene werden
     * zusätzlich als Text festgehalten, damit der Eintrag auch nach späteren Änderungen oder Löschungen lesbar bleibt.
     * Der Kontext enthält nie Passwörter, Codes, Tokens oder Geheimnisse.
     */
    public function up(): void
    {
        Schema::create('audit_entries', function (Blueprint $table): void {
            $table->id();

            $table->timestamp('occurred_at')->index();
            $table->string('event', 64)->index();

            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label', 200)->nullable();

            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label', 200)->nullable();

            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_entries');
    }
};
