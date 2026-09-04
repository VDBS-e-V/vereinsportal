<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'two_factor_methods',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                /*
                 * Initial:
                 * email
                 * totp
                 *
                 * Kein MySQL ENUM.
                 */
                $table->string('type', 32);

                /*
                 * Nur für Methoden mit Secret, insbesondere TOTP.
                 *
                 * Der Wert wird auf Model-Ebene verschlüsselt
                 * gespeichert. TEXT bietet ausreichend Platz
                 * für Laravels verschlüsseltes Payload.
                 *
                 * Für E-Mail-2FA bleibt secret NULL.
                 */
                $table
                    ->text('secret')
                    ->nullable();

                /*
                 * Eine vorbereitete TOTP-Methode ist erst nach
                 * erfolgreicher Erstbestätigung aktiv.
                 */
                $table
                    ->timestamp('confirmed_at')
                    ->nullable();

                /*
                 * Deaktivierte Methoden bleiben zur technischen
                 * Historie erhalten.
                 */
                $table
                    ->timestamp('disabled_at')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'user_id',
                    'type',
                    'disabled_at',
                ]);

                $table->index([
                    'user_id',
                    'confirmed_at',
                    'disabled_at',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'two_factor_methods'
        );
    }
};
