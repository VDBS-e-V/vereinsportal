<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_soft_bounces', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 190)->unique();
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('last_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mail_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mail_template_id')->constrained('mail_templates')->cascadeOnDelete();
            $table->string('name', 120)->nullable();
            $table->string('subject', 200);
            $table->text('body');
            $table->foreignId('saved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_template_versions');
        Schema::dropIfExists('mail_soft_bounces');
    }
};
