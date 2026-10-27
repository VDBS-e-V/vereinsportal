<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Modul: Access.
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
                 * Ausschliesslich Hash. Der Klartext wird nur einmal,
                 * direkt nach der Erzeugung, an den Benutzer ausgegeben.
                 */
                $table->string('code_hash', 255);

                $table->timestamp('used_at')->nullable();

                /*
                 * Bei Regeneration werden noch ungenutzte alte Codes
                 * invalidiert statt wiederverwendet.
                 */
                $table->timestamp('invalidated_at')->nullable();

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
        Schema::dropIfExists('two_factor_recovery_codes');
    }
};
