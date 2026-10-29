<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Modul: Membership.
     *
     * Eine Mitgliedschaft einer Person: Antrag, Aufnahme, Austritt. Pro Person gibt es höchstens eine offene
     * (beantragte oder laufende) Mitgliedschaft; frühere bleiben als Verlauf erhalten. Die Verwaltung ist dem Vorstand
     * vorbehalten, Mitgliedschaftsdaten erscheinen nicht in den Personenansichten und nicht im Protokoll.
     */
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('person_id')->constrained('persons')->restrictOnDelete();

            // requested | active | ended | rejected
            $table->string('status', 20)->index();

            $table->date('requested_on')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();

            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['person_id', 'status']);
        });

        Schema::create('membership_events', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('membership_id')->constrained('memberships')->cascadeOnDelete();

            $table->string('type', 32);
            $table->timestamp('occurred_at');

            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label', 200)->nullable();

            $table->text('comment')->nullable();
            $table->json('data')->nullable();

            $table->index(['membership_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_events');
        Schema::dropIfExists('memberships');
    }
};
