<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Aufgabensystem, zweiter Schritt: Labels, Unteraufgaben, Anhänge, Vorlagen und Merker für Fälligkeits-Erinnerungen. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_labels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_project_id')->constrained('task_projects')->cascadeOnDelete();
            $table->string('name', 40);
            $table->string('color', 12)->default('gray');
            $table->timestamps();
            $table->unique(['task_project_id', 'name']);
        });

        Schema::create('task_label_task', function (Blueprint $table): void {
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('task_label_id')->constrained('task_labels')->cascadeOnDelete();
            $table->primary(['task_id', 'task_label_id']);
        });

        Schema::create('task_subtasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('title', 200);
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
        });

        Schema::create('task_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('original_name', 190);
            $table->string('path', 255);
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('task_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_project_id')->constrained('task_projects')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('priority', 10)->default('normal');
            $table->unsignedSmallInteger('due_in_days')->nullable();
            $table->text('subtasks')->nullable();
            $table->timestamps();
            $table->unique(['task_project_id', 'name']);
        });

        Schema::create('task_template_label', function (Blueprint $table): void {
            $table->foreignId('task_template_id')->constrained('task_templates')->cascadeOnDelete();
            $table->foreignId('task_label_id')->constrained('task_labels')->cascadeOnDelete();
            $table->primary(['task_template_id', 'task_label_id']);
        });

        Schema::table('task_assignees', function (Blueprint $table): void {
            $table->timestamp('due_reminded_at')->nullable();
            $table->timestamp('overdue_reminded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('task_assignees', function (Blueprint $table): void {
            $table->dropColumn(['due_reminded_at', 'overdue_reminded_at']);
        });
        Schema::dropIfExists('task_template_label');
        Schema::dropIfExists('task_templates');
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_subtasks');
        Schema::dropIfExists('task_label_task');
        Schema::dropIfExists('task_labels');
    }
};
