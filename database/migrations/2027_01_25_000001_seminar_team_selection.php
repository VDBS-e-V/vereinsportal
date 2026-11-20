<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Teamauswahl: Entwurf je Interessent, Ergebnis nach „Team bestätigen“, Warteliste und Seminarleitung. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_applications', function (Blueprint $table): void {
            $table->string('decision', 8)->nullable()->after('status');
            $table->string('outcome', 10)->nullable()->after('decision');
            $table->unsignedSmallInteger('waitlist_position')->nullable()->after('outcome');
            $table->timestamp('notified_at')->nullable()->after('waitlist_position');
        });

        Schema::table('seminars', function (Blueprint $table): void {
            $table->foreignId('lead_user_id')->nullable()->after('application_deadline')->constrained('users')->nullOnDelete();
            $table->timestamp('team_confirmed_at')->nullable()->after('lead_user_id');
            $table->foreignId('team_confirmed_by_user_id')->nullable()->after('team_confirmed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('team_confirmed_by_user_id');
            $table->dropConstrainedForeignId('lead_user_id');
            $table->dropColumn('team_confirmed_at');
        });

        Schema::table('seminar_applications', function (Blueprint $table): void {
            $table->dropColumn(['decision', 'outcome', 'waitlist_position', 'notified_at']);
        });
    }
};
