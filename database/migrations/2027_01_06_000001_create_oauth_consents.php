<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Zustimmung einer Person zur Weitergabe ihrer Daten an ein angebundenes System (einmal je System, bis zum Widerruf).
        Schema::create('oauth_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('client_id', 100);
            $table->json('scopes');
            $table->timestamp('granted_at');
            $table->timestamps();
            $table->unique(['user_id', 'client_id']);
        });

        Schema::table('oauth_client_settings', function (Blueprint $table): void {
            $table->string('logo_path', 255)->nullable()->after('contact');
            $table->string('privacy_url', 255)->nullable()->after('logo_path');
            $table->text('purpose')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('oauth_client_settings', function (Blueprint $table): void {
            $table->dropColumn(['logo_path', 'privacy_url', 'purpose']);
        });

        Schema::dropIfExists('oauth_consents');
    }
};
