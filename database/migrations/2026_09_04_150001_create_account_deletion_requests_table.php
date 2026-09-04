<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletion_requests', function (Blueprint $table): void {
            $table->id();

            $table
                ->char('public_id', 26)
                ->unique();

            $table
                ->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table
                ->char('anonymous_user_ref', 26)
                ->nullable()
                ->index();

            $table
                ->string('status', 32)
                ->index();

            $table->timestamp('requested_at');

            $table
                ->timestamp('confirmation_sent_at')
                ->nullable();

            $table
                ->timestamp('confirmed_at')
                ->nullable();

            $table
                ->timestamp('revoke_until')
                ->nullable()
                ->index();

            $table
                ->timestamp('withdrawn_at')
                ->nullable();

            $table
                ->timestamp('stopped_at')
                ->nullable();

            $table
                ->foreignId('stopped_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table
                ->foreignId('stop_reason_id')
                ->nullable()
                ->constrained('account_deletion_stop_reasons')
                ->restrictOnDelete();

            $table
                ->text('stop_comment')
                ->nullable();

            $table
                ->timestamp('completed_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'status',
                'revoke_until',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
    }
};
