<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_contacts', function (Blueprint $table): void {
            // import = Mailingliste oder von Hand eingetragen, link = über den allgemeinen Bewerbungslink gekommen
            $table->string('origin', 10)->default('import')->after('email');
        });

        Schema::table('seminar_applications', function (Blueprint $table): void {
            $table->timestamp('confirmed_at')->nullable();
        });

        // Bewerbungen über den persönlichen Zugang (Teil 2) gelten als bestätigt: Die Adresse ist durch den Zugang belegt.
        DB::table('seminar_applications')->whereNotNull('contact_id')->update(['confirmed_at' => DB::raw('created_at')]);

        Schema::table('seminars', function (Blueprint $table): void {
            $table->timestamp('team_informed_at')->nullable();
            $table->string('team_informed_key', 64)->nullable();
        });

        Schema::create('seminar_team_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->string('token_hash', 64)->index();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedInteger('uses')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_team_invites');

        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropColumn(['team_informed_at', 'team_informed_key']);
        });

        Schema::table('seminar_applications', function (Blueprint $table): void {
            $table->dropColumn('confirmed_at');
        });

        Schema::table('team_contacts', function (Blueprint $table): void {
            $table->dropColumn('origin');
        });
    }
};
