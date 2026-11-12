<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Modul: Access. Zeitpunkt, zu dem ein Nutzer die Löschung seines Kontos beantragt hat (Status „Löschung angefragt“).
     * Nach der Wartefrist anonymisiert `account:purge` das Konto.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('deletion_requested_at')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('deletion_requested_at');
        });
    }
};
