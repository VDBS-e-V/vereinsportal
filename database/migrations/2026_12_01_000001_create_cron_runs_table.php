<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 12)->default('web');
            $table->timestamp('started_at')->index();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('jobs_run')->default(0);
            $table->string('status', 12)->default('running');
            $table->string('error', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_runs');
    }
};
