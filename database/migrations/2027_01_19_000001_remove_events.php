<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Entfernt die Veranstaltungen (Großveranstaltungen mit Anmeldung, Tickets, Schul-Listen) samt Anmeldungen und Anwesenheiten.
 * Rückgängig machen lässt sich das nur über die Sicherung.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const TABLES = ['school_event_reminders', 'event_messages', 'event_attendances', 'event_registrations', 'event_sessions', 'events'];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Die Daten sind nicht wiederherstellbar; die Tabellen legen die früheren Migrationen an.
    }
};
