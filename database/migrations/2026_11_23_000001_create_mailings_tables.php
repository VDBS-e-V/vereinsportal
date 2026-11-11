<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('subject', 200);
            $table->text('body');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mailings', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 20)->index();
            $table->string('subject', 200);
            $table->text('body');
            $table->text('own_text')->nullable();
            $table->foreignId('seminar_id')->nullable()->constrained('seminars')->nullOnDelete();
            $table->string('audience', 30)->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailings');
        Schema::dropIfExists('mail_templates');
    }
};
