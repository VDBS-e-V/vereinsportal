<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Geänderte Fassungen der Systemmails (system_key) und frei benannte Vorlagen, mit Blöcken des Mail-Builders
        Schema::create('mail_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('kind', 20)->default('system');
            $table->string('name', 120)->nullable();
            $table->string('description', 250)->nullable();
            $table->string('category', 40)->nullable();
            $table->string('audience', 20)->nullable();
            $table->string('subject', 200);
            $table->text('body');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('system_key', 80)->nullable()->unique();
            $table->unsignedBigInteger('layout_id')->nullable();
            $table->json('blocks')->nullable();
            $table->string('preheader', 200)->nullable();
            $table->text('plain_text')->nullable();
            $table->string('status', 20)->default('published');
            $table->timestamps();
        });

        Schema::create('mail_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mail_template_id')->constrained('mail_templates')->cascadeOnDelete();
            $table->string('name', 120)->nullable();
            $table->string('subject', 200);
            $table->text('body');
            $table->foreignId('saved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('blocks')->nullable();
            $table->unsignedBigInteger('layout_id')->nullable();
            $table->string('preheader', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_template_versions');
        Schema::dropIfExists('mail_templates');
    }
};
