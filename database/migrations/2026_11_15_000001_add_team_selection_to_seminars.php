<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->date('application_deadline')->nullable()->after('team_needed');
            $table->json('team_competences')->nullable()->after('application_deadline');
            $table->timestamp('deadline_notified_at')->nullable()->after('team_competences');
        });

        Schema::table('seminar_applications', function (Blueprint $table): void {
            $table->boolean('wants_lead')->default(false)->after('note');
            $table->string('internal_note', 300)->nullable()->after('wants_lead');
        });

        Schema::create('team_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('competences')->nullable();
            $table->text('experience')->nullable();
            $table->boolean('lead_experience')->default(false);
            $table->string('gender', 10)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_profiles');
        Schema::table('seminar_applications', function (Blueprint $table): void {
            $table->dropColumn(['wants_lead', 'internal_note']);
        });
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropColumn(['application_deadline', 'team_competences', 'deadline_notified_at']);
        });
    }
};
