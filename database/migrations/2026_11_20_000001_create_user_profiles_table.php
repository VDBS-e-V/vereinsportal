<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name', 80)->nullable();
            $table->string('headline', 120)->nullable();
            $table->text('bio')->nullable();
            // Sichtbarkeit je Angabe: private | portal | public (ohne Eintrag gilt private)
            $table->json('visibility')->nullable();
            // Zeitpunkt der letzten Einwilligung zur Veröffentlichung
            $table->timestamp('consent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
