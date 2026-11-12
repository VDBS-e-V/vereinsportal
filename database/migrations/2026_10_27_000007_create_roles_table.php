<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Modul: Access-Governance.
     *
     * Rollen-Katalog. Seed-Werte (key): guest, member, board_member,
     * team, coordination, administration_staff, education_coordination,
     * administration. Die zehn Portal-Bereiche selbst sind KEINE Tabelle,
     * sondern eine Konfiguration in der Anwendungsschicht, die Rollen auf
     * sichtbare Bereiche/Capabilities abbildet.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();

            $table->string('key', 64)->unique();
            $table->string('name', 100);
            $table->boolean('is_system');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
