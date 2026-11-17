<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Seminarablauf Phase 5: Stichtag und Erinnerung für Berichte, Bericht an die Schule, Ergebnislink. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->date('report_due_at')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamp('report_reminded_at')->nullable();
            $table->timestamp('report_overdue_notified_at')->nullable();
            $table->timestamp('report_sent_at')->nullable();
            $table->string('results_share_url', 500)->nullable();
            $table->date('results_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropColumn(['report_due_at', 'reported_at', 'report_reminded_at', 'report_overdue_notified_at', 'report_sent_at', 'results_share_url', 'results_expires_at']);
        });
    }
};
