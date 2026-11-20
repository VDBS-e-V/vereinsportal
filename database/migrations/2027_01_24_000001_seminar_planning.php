<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Planung und Ausschreibung: Sollzahl, Bewerbungsfrist und das Interesse der Teamenden. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->unsignedSmallInteger('team_target')->nullable()->after('participants');
            $table->date('application_deadline')->nullable()->after('team_target');
        });

        Schema::create('seminar_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('availability')->nullable();
            $table->string('status', 12)->default('applied')->index();
            $table->timestamps();
            $table->unique(['seminar_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_applications');

        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropColumn(['team_target', 'application_deadline']);
        });
    }
};
