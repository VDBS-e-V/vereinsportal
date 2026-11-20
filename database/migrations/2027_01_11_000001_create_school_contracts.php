<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->unsignedInteger('template_version');
            $table->string('title', 150);
            $table->longText('body');
            $table->string('status', 20)->default('draft');
            $table->date('signed_on')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signed_recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['seminar_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_contracts');
    }
};
