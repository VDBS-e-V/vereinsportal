<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_layouts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mail_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('path', 255);
            $table->string('original_name', 190);
            $table->string('mime', 60);
            $table->string('alt_default', 200)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_assets');
        Schema::dropIfExists('mail_layouts');
    }
};
