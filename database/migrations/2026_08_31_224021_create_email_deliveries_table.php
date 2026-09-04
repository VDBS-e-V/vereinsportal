<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_deliveries', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('template_version_id')
                ->nullable()
                ->constrained('email_template_versions')
                ->restrictOnDelete();

            $table
                ->foreignId('sender_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('recipient_email', 254);
            $table->string('subject', 255);
            $table->string('delivery_type', 20);
            $table->string('status', 20);
            $table->unsignedTinyInteger('attempts');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('last_error_class', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_deliveries');
    }
};
