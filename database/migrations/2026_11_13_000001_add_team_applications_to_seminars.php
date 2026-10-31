<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->unsignedSmallInteger('team_needed')->nullable()->after('expected_participants');
        });

        Schema::create('seminar_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('applied');
            $table->string('note', 300)->nullable();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['seminar_id', 'user_id']);
            $table->index('status');
        });

        Schema::table('team_onboardings', function (Blueprint $table): void {
            $table->timestamp('last_reminded_at')->nullable();
            $table->unsignedTinyInteger('reminders_sent')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('team_onboardings', function (Blueprint $table): void {
            $table->dropColumn(['last_reminded_at', 'reminders_sent']);
        });
        Schema::dropIfExists('seminar_applications');
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropColumn('team_needed');
        });
    }
};
