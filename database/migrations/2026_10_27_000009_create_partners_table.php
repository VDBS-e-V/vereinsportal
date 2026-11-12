<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Modul: Partners.
     *
     * Generisch fuer Partnerorganisationen ohne eigene Mitgliedschaft
     * (aktuell: Schulen; type = "school"). Weitere Partner-Typen (z. B.
     * Sponsoren, andere Vereine) werden ueber `type` unterschieden statt
     * ueber eigene Tabellen. `attributes` traegt typ-spezifische
     * Zusatzfelder (z. B. school_type, student_count); erst wenn ein Typ
     * eigene *Beziehungen* braucht, lohnt sich eine Zusatztabelle.
     *
     * Verwaltung laut Konzept durch die Rollen coordination und
     * administration_staff (Koordination, Geschaeftsstelle).
     */
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table): void {
            $table->id();

            $table->char('public_id', 26)->unique();

            $table->string('type', 50);

            $table->string('name', 200);
            $table->string('legal_name', 200)->nullable();

            $table->string('street', 150)->nullable();
            $table->string('house_number', 30)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 120)->nullable();
            $table->char('country_code', 2)->default('DE');

            // Entweder bereits bekannte Person, oder freie Kontaktangaben.
            $table
                ->foreignId('contact_person_id')
                ->nullable()
                ->constrained('persons')
                ->nullOnDelete();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_email', 254)->nullable();
            $table->string('contact_phone', 50)->nullable();

            $table->string('status', 32)->default('active');

            $table->json('attributes')->nullable();
            $table->text('notes')->nullable();

            $table
                ->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table
                ->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('type');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
