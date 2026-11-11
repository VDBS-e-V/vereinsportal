<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_snippets', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->text('body');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mailing_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mailing_id')->constrained('mailings')->cascadeOnDelete();
            $table->string('original_name', 200);
            $table->string('path', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_attachments');
        Schema::dropIfExists('mail_snippets');
    }
};
