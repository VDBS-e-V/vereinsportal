<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_applications', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreignId('contact_id')->nullable()->after('user_id')->constrained('team_contacts')->cascadeOnDelete();
            $table->string('guest_name', 150)->nullable();
            $table->string('guest_phone', 50)->nullable();
            $table->unsignedSmallInteger('guest_birth_year')->nullable();
            $table->json('guest_competences')->nullable();
            $table->text('guest_experience')->nullable();
            $table->boolean('guest_consent_contact')->default(false);

            $table->unique(['seminar_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::table('seminar_applications', function (Blueprint $table): void {
            $table->dropUnique(['seminar_id', 'contact_id']);
            $table->dropForeign(['contact_id']);
            $table->dropColumn(['contact_id', 'guest_name', 'guest_phone', 'guest_birth_year', 'guest_competences', 'guest_experience', 'guest_consent_contact']);
        });
    }
};
