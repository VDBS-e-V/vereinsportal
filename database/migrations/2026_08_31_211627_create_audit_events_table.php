<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();

            $table->timestamp('occurred_at');
            $table->string('event_key', 100);

            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_context', 50)->nullable();
            $table->char('actor_anonymous_ref', 26)->nullable();

            $table->string('subject_type', 150)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->char('subject_anonymous_ref', 26)->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->text('comment')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('device_info')->nullable();

            $table->timestamp('retention_until');

            $table->timestamp('created_at')->useCurrent();

            $table->index('event_key');
            $table->index('occurred_at');
            $table->index('retention_until');
            $table->index('actor_user_id');
            $table->index([
                'subject_type',
                'subject_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};