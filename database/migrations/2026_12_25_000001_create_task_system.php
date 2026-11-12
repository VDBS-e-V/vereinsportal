<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Das allgemeine Aufgabensystem: Projekte mit Spalten (Status), Mitgliedern, Aufgaben, Zuständigen und Kommentaren.
 * Die Vorstandsaufgaben aus board_tasks ziehen in das Projekt „Vorstand“ um (Rolle Vorstand ist automatisch Mitglied).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_projects', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 60)->nullable()->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('role_key', 40)->nullable();
            $table->boolean('is_archived')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('task_project_members', function (Blueprint $table): void {
            $table->foreignId('task_project_id')->constrained('task_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 10)->default('member');
            $table->primary(['task_project_id', 'user_id']);
        });

        Schema::create('task_statuses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_project_id')->constrained('task_projects')->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_done')->default(false);
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_project_id')->constrained('task_projects')->cascadeOnDelete();
            $table->foreignId('task_status_id')->constrained('task_statuses');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('priority', 10)->default('normal');
            $table->date('due_on')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('done_at')->nullable();
            $table->foreignId('board_meeting_id')->nullable()->constrained('board_meetings')->nullOnDelete();
            $table->foreignId('board_resolution_id')->nullable()->constrained('board_resolutions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['task_project_id', 'task_status_id', 'position']);
            $table->index('due_on');
        });

        Schema::create('task_assignees', function (Blueprint $table): void {
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['task_id', 'user_id']);
        });

        Schema::create('task_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        $this->moveBoardTasks();
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_statuses');
        Schema::dropIfExists('task_project_members');
        Schema::dropIfExists('task_projects');
    }

    private function moveBoardTasks(): void
    {
        if (! Schema::hasTable('board_tasks')) {
            return;
        }

        $old = DB::table('board_tasks')->orderBy('id')->get();

        if ($old->isNotEmpty()) {
            $now = now();
            $project = DB::table('task_projects')->insertGetId(['slug' => 'vorstand', 'name' => 'Vorstand', 'description' => 'Aufgaben aus den Vorstandssitzungen und Beschlüssen.', 'role_key' => 'board_member', 'created_at' => $now, 'updated_at' => $now]);
            $statuses = [];

            foreach ([['Offen', 0, false], ['In Arbeit', 1, false], ['Wartet', 2, false], ['Erledigt', 3, true]] as [$name, $position, $done]) {
                $statuses[$name] = DB::table('task_statuses')->insertGetId(['task_project_id' => $project, 'name' => $name, 'position' => $position, 'is_done' => $done, 'created_at' => $now, 'updated_at' => $now]);
            }

            $positions = [];

            foreach ($old as $task) {
                $status = $task->done_at !== null ? $statuses['Erledigt'] : $statuses['Offen'];
                $positions[$status] = ($positions[$status] ?? -1) + 1;

                $id = DB::table('tasks')->insertGetId([
                    'task_project_id' => $project, 'task_status_id' => $status, 'title' => $task->title, 'description' => $task->notes, 'priority' => 'normal', 'due_on' => $task->due_on,
                    'position' => $positions[$status], 'done_at' => $task->done_at, 'board_meeting_id' => $task->board_meeting_id, 'board_resolution_id' => $task->board_resolution_id,
                    'created_by' => $task->created_by, 'created_at' => $task->created_at, 'updated_at' => $task->updated_at,
                ]);

                foreach (DB::table('board_task_user')->where('board_task_id', $task->id)->pluck('user_id') as $userId) {
                    DB::table('task_assignees')->insert(['task_id' => $id, 'user_id' => $userId]);
                }
            }
        }

        Schema::dropIfExists('board_task_user');
        Schema::dropIfExists('board_tasks');
    }
};
