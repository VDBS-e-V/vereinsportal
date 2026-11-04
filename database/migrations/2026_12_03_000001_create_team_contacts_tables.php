<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 30);
            $table->string('file_name', 190)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_new')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->timestamp('consent_confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('team_contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 190)->unique();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('street', 150)->nullable();
            $table->string('house_number', 20)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city', 100)->nullable();
            $table->json('competences')->nullable();
            $table->boolean('profile_saved')->default(false);
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->timestamp('consent_confirmed_at')->nullable();
            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('access_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_id')->constrained('team_contacts')->cascadeOnDelete();
            $table->string('token_hash', 64)->index();
            $table->string('code_hash', 64);
            $table->string('code_hint', 2);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('uses')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_credentials');
        Schema::dropIfExists('team_contacts');
        Schema::dropIfExists('import_batches');
    }
};
