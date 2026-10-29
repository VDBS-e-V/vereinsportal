<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Modul: Membership.
     *
     * Drei Wege zu einer Mitgliedschaft:
     *  - manuell: Der Vorstand erfasst einen Antrag oder Altbestand mit Dokument, ohne digitale Person (person_id leer).
     *  - online: Der Antrag legt Person und Konto an, der Vorstand bestätigt.
     *  - Registrierungslink: Eine manuelle Mitgliedschaft wird per Link mit Person und Konto verknüpft.
     * Name, Geburtsdatum und E-Mail stehen deshalb auch an der Mitgliedschaft selbst.
     */
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table): void {
            $table->foreignId('person_id')->nullable()->change();

            $table->string('first_name', 100)->nullable()->after('person_id');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->date('birth_date')->nullable()->after('last_name');
            $table->string('email', 254)->nullable()->after('birth_date');

            // manual | online
            $table->string('source', 10)->default('manual')->after('status');

            // Registrierungslink: nur der Hash wird gespeichert
            $table->string('registration_token_hash', 64)->nullable()->unique();
            $table->timestamp('registration_sent_at')->nullable();
            $table->timestamp('registration_expires_at')->nullable();
            $table->timestamp('linked_at')->nullable();

            $table->index('email');
        });

        Schema::create('membership_documents', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('membership_id')->constrained('memberships')->cascadeOnDelete();

            // application | record | other
            $table->string('kind', 20);
            $table->string('original_name', 200);
            $table->string('path', 300);
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->string('sha256', 64);

            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at');

            $table->index('membership_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_documents');

        Schema::table('memberships', function (Blueprint $table): void {
            $table->dropUnique(['registration_token_hash']);
            $table->dropIndex(['email']);
            $table->dropColumn(['first_name', 'last_name', 'birth_date', 'email', 'source', 'registration_token_hash', 'registration_sent_at', 'registration_expires_at', 'linked_at']);
        });
    }
};
