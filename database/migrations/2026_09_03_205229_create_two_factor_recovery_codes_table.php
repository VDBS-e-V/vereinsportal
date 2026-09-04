<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'two_factor_recovery_codes',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                /*
                 * Ausschließlich Hash.
                 * Der Klartext wird später genau einmal nach
                 * der Erzeugung an den Benutzer ausgegeben.
                 */
                $table->string('code_hash', 255);

                /*
                 * Recovery Code erfolgreich eingesetzt.
                 */
                $table
                    ->timestamp('used_at')
                    ->nullable();

                /*
                 * Bei Regeneration werden noch ungenutzte alte
                 * Codes invalidiert statt wiederverwendet.
                 */
                $table
                    ->timestamp('invalidated_at')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'user_id',
                    'used_at',
                    'invalidated_at',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'two_factor_recovery_codes'
        );
    }
};
