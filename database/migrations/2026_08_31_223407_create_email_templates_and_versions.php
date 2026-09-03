<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name', 150);
            $table->boolean('is_active');
            $table->string('draft_subject', 255);
            $table->longText('draft_html');

            $table
                ->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
        });

        Schema::create('email_template_versions', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('email_template_id')
                ->constrained('email_templates')
                ->restrictOnDelete();

            $table->unsignedInteger('version');
            $table->string('subject', 255);
            $table->longText('html');

            $table
                ->foreignId('published_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('published_at');

            $table->unique([
                'email_template_id',
                'version',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_versions');
        Schema::dropIfExists('email_templates');
    }
};