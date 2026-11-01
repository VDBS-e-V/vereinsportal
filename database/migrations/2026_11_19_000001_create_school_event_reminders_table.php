<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_event_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('partner_user_id')->constrained('partner_users')->cascadeOnDelete();
            $table->timestamp('sent_at');

            $table->unique(['event_id', 'partner_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_event_reminders');
    }
};
