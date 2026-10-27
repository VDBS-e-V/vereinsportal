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
            'two_factor_email_challenges',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                // Ausschliesslich Hash des E-Mail-Codes, niemals Klartext.
                $table->string('code_hash', 255);

                $table->timestamp('expires_at');

                // Gesetzt, sobald die Versandvorbereitung erfolgreich war.
                $table->timestamp('sent_at')->nullable();

                // Erfolgreich verbrauchter Challenge.
                $table->timestamp('used_at')->nullable();

                // Ein neuer Challenge invalidiert den vorherigen offenen.
                $table->timestamp('invalidated_at')->nullable();

                $table->timestamps();

                $table->index([
                    'user_id',
                    'used_at',
                    'invalidated_at',
                ]);
                $table->index(['user_id', 'expires_at']);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_email_challenges');
    }
};
