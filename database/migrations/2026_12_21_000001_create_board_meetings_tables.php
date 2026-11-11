<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_meetings', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 150)->default('Vorstandssitzung');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('location', 200)->nullable();
            $table->string('online_link', 500)->nullable();
            $table->string('status', 20)->default('planned');
            $table->longText('agenda')->nullable();
            $table->longText('minutes')->nullable();
            $table->date('minutes_approved_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('starts_at');
        });

        Schema::create('board_meeting_user', function (Blueprint $table): void {
            $table->foreignId('board_meeting_id')->constrained('board_meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 10);
            $table->primary(['board_meeting_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_meeting_user');
        Schema::dropIfExists('board_meetings');
    }
};
