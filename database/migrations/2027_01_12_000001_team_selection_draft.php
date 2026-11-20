<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Teamauswahl als Entwurf (docs/87, Phase 3): Entwurf je Bewerbung, Warteliste, Zeitpunkt der Bestätigung. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_applications', function (Blueprint $table): void {
            $table->string('decision_draft', 10)->nullable();
            $table->unsignedSmallInteger('waitlist_position')->nullable();
            $table->timestamp('confirmation_sent_at')->nullable();
        });

        Schema::table('seminars', function (Blueprint $table): void {
            $table->timestamp('team_confirmed_at')->nullable();
            $table->foreignId('team_confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('team_confirmed_by_user_id');
            $table->dropColumn('team_confirmed_at');
        });

        Schema::table('seminar_applications', function (Blueprint $table): void {
            $table->dropColumn(['decision_draft', 'waitlist_position', 'confirmation_sent_at']);
        });
    }
};
