<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Seminarablauf Phase 4: Onboarding-Termin und Briefing der Schule. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->timestamp('onboarding_at')->nullable();
            $table->string('onboarding_link', 255)->nullable();
            $table->timestamp('briefing_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropColumn(['onboarding_at', 'onboarding_link', 'briefing_sent_at']);
        });
    }
};
