<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Modul: People.
     *
     * Stammdaten echter Personen, unabhaengig vom Login-Zustand (users.
     * person_id ist nullable: eine Person kann ohne Benutzerkonto
     * existieren, z. B. bevor eine Registrierung abgeschlossen ist).
     */
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table): void {
            $table->id();

            $table->string('title', 50)->nullable();
            $table->string('first_name', 100);
            $table->string('name_addition', 100)->nullable();
            $table->string('last_name', 100);
            $table->date('birth_date');

            $table->string('email', 254)->unique();
            $table->string('phone', 50)->nullable();

            $table->string('street', 150)->nullable();
            $table->string('house_number', 30)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 120)->nullable();
            $table->char('country_code', 2)->default('DE');

            $table->timestamps();

            $table->index([
                'last_name',
                'first_name',
                'birth_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
