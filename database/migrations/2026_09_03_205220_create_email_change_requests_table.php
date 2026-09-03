<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'email_change_requests',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->char('public_id', 26)
                    ->unique();

                $table
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->string('old_email', 254);
                $table->string('new_email', 254);

                /*
                 * pending
                 * superseded
                 * confirmed
                 *
                 * Kein MySQL ENUM.
                 */
                $table->string('status', 32);

                $table->timestamp('expires_at');

                $table
                    ->timestamp('verification_sent_at')
                    ->nullable();

                $table
                    ->timestamp('confirmed_at')
                    ->nullable();

                $table
                    ->timestamp('superseded_at')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'user_id',
                    'status',
                ]);

                $table->index([
                    'new_email',
                    'status',
                ]);

                $table->index([
                    'status',
                    'expires_at',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'email_change_requests'
        );
    }
};