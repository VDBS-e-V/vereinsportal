<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Modul: Membership.
     *
     * Einwilligungsnachweise: Welche Zustimmung (Satzung, Datenschutzhinweise) wurde zu welchem Zeitpunkt in welcher
     * Fassung gegeben? Nur anlegen, nie ändern. Dazu ein Merker, ob der Vorstand über einen Online-Antrag informiert wurde.
     */
    public function up(): void
    {
        Schema::create('membership_consents', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('membership_id')->constrained('memberships')->cascadeOnDelete();

            // statute | privacy
            $table->string('type', 20);
            $table->string('version', 40);
            $table->timestamp('accepted_at');

            $table->index(['membership_id', 'type']);
        });

        Schema::table('memberships', function (Blueprint $table): void {
            $table->timestamp('board_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table): void {
            $table->dropColumn('board_notified_at');
        });

        Schema::dropIfExists('membership_consents');
    }
};
