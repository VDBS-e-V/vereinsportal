<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Nextcloud: Ordner des Seminars, Freigabe des Ordners „Ergebnisse“ für die Schule und der letzte Fehler. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->string('nextcloud_path', 500)->nullable()->after('report_overdue_notified_at');
            $table->string('nextcloud_share_id', 50)->nullable()->after('nextcloud_path');
            $table->timestamp('nextcloud_synced_at')->nullable()->after('nextcloud_share_id');
            $table->text('nextcloud_error')->nullable()->after('nextcloud_synced_at');
            $table->boolean('nextcloud_manual')->default(false)->after('nextcloud_error');
        });
    }

    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropColumn(['nextcloud_path', 'nextcloud_share_id', 'nextcloud_synced_at', 'nextcloud_error', 'nextcloud_manual']);
        });
    }
};
