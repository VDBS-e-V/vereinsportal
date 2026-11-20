<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Fristen: Hinweis vor dem Ablauf des Ergebnislinks (einmal je Seminar). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->timestamp('results_expiry_notified_at')->nullable()->after('nextcloud_manual');
        });
    }

    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropColumn('results_expiry_notified_at');
        });
    }
};
