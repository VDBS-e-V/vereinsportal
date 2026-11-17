<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Seminarablauf Phase 6: Nextcloud-Ordner und Ergebnislink. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->string('nextcloud_folder_path', 500)->nullable();
            $table->string('nextcloud_status', 20)->nullable();
            $table->text('nextcloud_error')->nullable();
            $table->timestamp('nextcloud_synced_at')->nullable();
            $table->boolean('nextcloud_manual')->default(false);
            $table->string('results_share_id', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropColumn(['nextcloud_folder_path', 'nextcloud_status', 'nextcloud_error', 'nextcloud_synced_at', 'nextcloud_manual', 'results_share_id']);
        });
    }
};
