<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_resolutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_meeting_id')->constrained('board_meetings')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('seq');
            $table->string('title', 200);
            $table->longText('text')->nullable();
            $table->string('outcome', 12)->default('accepted');
            $table->unsignedSmallInteger('votes_for')->nullable();
            $table->unsignedSmallInteger('votes_against')->nullable();
            $table->unsignedSmallInteger('votes_abstain')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['year', 'seq']);
        });

        Schema::create('board_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_meeting_id')->nullable()->constrained('board_meetings')->nullOnDelete();
            $table->foreignId('board_resolution_id')->nullable()->constrained('board_resolutions')->nullOnDelete();
            $table->string('title', 200);
            $table->text('notes')->nullable();
            $table->date('due_on')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['done_at', 'due_on']);
        });

        Schema::create('board_task_user', function (Blueprint $table): void {
            $table->foreignId('board_task_id')->constrained('board_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['board_task_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_task_user');
        Schema::dropIfExists('board_tasks');
        Schema::dropIfExists('board_resolutions');
    }
};
