<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->string('number', 20)->nullable()->unique()->after('id');
            $table->unsignedSmallInteger('classes_count')->nullable();
            $table->string('location_notes', 500)->nullable();
            $table->string('cost_center', 40)->nullable();
            $table->foreignId('coordinator_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('seminar_counters', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_counters');

        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropForeign(['coordinator_user_id']);
            $table->dropUnique(['number']);
            $table->dropColumn(['number', 'classes_count', 'location_notes', 'cost_center', 'coordinator_user_id']);
        });
    }
};
