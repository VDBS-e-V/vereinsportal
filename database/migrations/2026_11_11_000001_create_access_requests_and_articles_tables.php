<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anträge auf Zugang zum Portal (öffentliches Formular). Nachweise werden nach der Entscheidung gelöscht.
        Schema::create('access_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('status', 20)->default('new');
            $table->string('connection', 20);
            $table->string('organization', 200)->nullable();
            $table->text('details')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 254);
            $table->date('birth_date');
            $table->string('phone', 50)->nullable();
            $table->timestamp('consented_at');
            $table->string('terms_version', 20);
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('email');
        });

        Schema::create('access_request_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('access_request_id')->constrained('access_requests')->cascadeOnDelete();
            $table->string('original_name', 200);
            $table->string('path', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamp('created_at')->nullable();
        });

        // Artikel für die öffentliche Startseite und die Artikelseiten
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 200);
            $table->string('teaser', 300)->nullable();
            $table->text('body');
            $table->string('category', 60)->default('Themenartikel');
            $table->string('image_path', 255)->nullable();
            $table->string('image_alt', 200)->nullable();
            $table->string('image_credit', 200)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('featured')->default(false);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
        Schema::dropIfExists('access_request_files');
        Schema::dropIfExists('access_requests');
    }
};
