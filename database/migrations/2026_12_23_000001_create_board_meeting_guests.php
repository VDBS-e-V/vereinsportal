<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_meeting_guests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_meeting_id')->constrained('board_meetings')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('email', 190);
            $table->string('organization', 150)->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();
            $table->unique(['board_meeting_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_meeting_guests');
    }
};
