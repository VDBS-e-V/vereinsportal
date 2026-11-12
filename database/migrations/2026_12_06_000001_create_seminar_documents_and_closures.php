<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('title', 200);
            $table->string('original_name', 200);
            $table->string('path', 255);
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->boolean('is_snapshot')->default(false);
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('retention_until')->nullable()->index();
            $table->timestamps();

            $table->index(['seminar_id', 'kind']);
        });

        Schema::create('seminar_closures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at');
            $table->json('checklist');
            $table->json('snapshot');
            $table->timestamp('reopened_at')->nullable();
            $table->foreignId('reopened_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reopen_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['seminar_id', 'reopened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_closures');
        Schema::dropIfExists('seminar_documents');
    }
};
