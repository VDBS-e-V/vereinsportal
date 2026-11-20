<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Abmelde-Adresse der Anbindung (OpenID Connect Front-Channel Logout): Beim Abmelden ruft der Browser sie auf, damit auch das andere System abmeldet.
        Schema::table('oauth_client_settings', function (Blueprint $table): void {
            $table->string('frontchannel_logout_uri', 255)->nullable()->after('contact');
        });
    }

    public function down(): void
    {
        Schema::table('oauth_client_settings', function (Blueprint $table): void {
            $table->dropColumn('frontchannel_logout_uri');
        });
    }
};
