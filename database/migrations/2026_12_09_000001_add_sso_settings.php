<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Stabile, nicht erratbare Kennung für externe Systeme (Subject der Anmeldung), statt der Datenbank-ID.
        Schema::table('users', function (Blueprint $table): void {
            $table->char('public_uuid', 36)->nullable()->unique()->after('id');
        });

        DB::table('users')->whereNull('public_uuid')->orderBy('id')->each(function ($user): void {
            DB::table('users')->where('id', $user->id)->update(['public_uuid' => (string) Str::uuid()]);
        });

        // Einstellungen je Anbindung (Client von Passport): erlaubte Rollen, Beschreibung, Zustimmungsseite.
        Schema::create('oauth_client_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('client_id', 100)->unique();
            $table->json('allowed_roles')->nullable();
            $table->text('description')->nullable();
            $table->string('contact', 190)->nullable();
            $table->boolean('show_consent')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_client_settings');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('public_uuid');
        });
    }
};
