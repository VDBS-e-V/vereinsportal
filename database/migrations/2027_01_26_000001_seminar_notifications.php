<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Mitteilungen: Onboarding-Termin, Briefing der Schule und die einmalige Fristerinnerung an Teamende. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dateTime('onboarding_at')->nullable()->after('team_confirmed_by_user_id');
            $table->string('onboarding_link', 500)->nullable()->after('onboarding_at');
            $table->timestamp('briefing_sent_at')->nullable()->after('onboarding_link');
            $table->timestamp('tender_reminded_at')->nullable()->after('briefing_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropColumn(['onboarding_at', 'onboarding_link', 'briefing_sent_at', 'tender_reminded_at']);
        });
    }
};
