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

        Schema::table('mail_templates', function (Blueprint $table): void {
            $table->string('system_key', 80)->nullable()->unique();
            $table->unsignedBigInteger('layout_id')->nullable();
            $table->json('blocks')->nullable();
            $table->string('preheader', 200)->nullable();
            $table->text('plain_text')->nullable();
            $table->string('status', 20)->default('published');
        });

        Schema::table('mail_template_versions', function (Blueprint $table): void {
            $table->json('blocks')->nullable();
            $table->unsignedBigInteger('layout_id')->nullable();
            $table->string('preheader', 200)->nullable();
        });

        Schema::table('mailings', function (Blueprint $table): void {
            $table->json('blocks')->nullable();
            $table->unsignedBigInteger('layout_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mailings', function (Blueprint $table): void {
            $table->dropColumn(['blocks', 'layout_id']);
        });

        Schema::table('mail_template_versions', function (Blueprint $table): void {
            $table->dropColumn(['blocks', 'layout_id', 'preheader']);
        });

        Schema::table('mail_templates', function (Blueprint $table): void {
            $table->dropUnique(['system_key']);
            $table->dropColumn(['system_key', 'layout_id', 'blocks', 'preheader', 'plain_text', 'status']);
        });

        Schema::dropIfExists('mail_assets');
        Schema::dropIfExists('mail_layouts');
    }
};
