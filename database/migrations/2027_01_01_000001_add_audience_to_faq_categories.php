<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** FAQ-Kategorien können auf Rollen beschränkt werden (z. B. Honorare nur für Teamende und Verwaltung). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faq_categories', function (Blueprint $table): void {
            $table->string('audience', 10)->default('public')->after('description'); // public | login | roles
            $table->json('roles')->nullable()->after('audience');
        });

        // Entwurf: Team, Verträge und Abrechnung nur für Teamende und Verwaltung; Konto und Daten nur für Angemeldete
        DB::table('faq_categories')->where('title', 'Teamarbeit, Verträge und Abrechnung')->update([
            'audience' => 'roles',
            'roles' => json_encode(['team', 'coordination', 'education_coordination', 'administration_staff', 'administration', 'managing_director', 'treasury', 'board_member', 'employee']),
        ]);
        DB::table('faq_categories')->where('title', 'Konto, E-Mail-Adresse und Daten')->update(['audience' => 'login']);
    }

    public function down(): void
    {
        Schema::table('faq_categories', function (Blueprint $table): void {
            $table->dropColumn(['audience', 'roles']);
        });
    }
};
