<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Früher: Vertragstext für Dienstleistungsverträge einspielen. Die Seminar-, Kostenvoranschlags- und Vertragsfunktionen wurden aus dem Portal entfernt
 * (siehe 2027_01_18_000001_remove_seminars_quotes_and_contracts); diese Migration bleibt als leere Stufe erhalten, damit die Reihenfolge der Migrationen stimmt.
 */
return new class extends Migration
{
    public function up(): void {}

    public function down(): void {}
};
