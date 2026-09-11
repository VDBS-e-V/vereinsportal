<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('membership_id')
                ->constrained('memberships')
                ->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('label', 150)->nullable();
            $table->string('disk', 50)->default('local');
            $table->string('path', 500)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->date('received_on')->nullable();
            $table->foreignId('uploaded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('supersedes_document_id')
                ->nullable()
                ->constrained('membership_documents')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['membership_id', 'document_type']);
            $table->index(['membership_id', 'created_at']);
            $table->index('sha256');
        });

        Schema::create('membership_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('membership_id')
                ->constrained('memberships')
                ->cascadeOnDelete();
            $table->string('consent_key', 100);
            $table->string('label', 150);
            $table->string('version', 50);
            $table->string('source', 50);
            $table->dateTime('granted_at');
            $table->dateTime('revoked_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('revoked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->index(['membership_id', 'consent_key', 'revoked_at']);
            $table->index(['membership_id', 'granted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_consents');
        Schema::dropIfExists('membership_documents');
    }
};
