<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_consents', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('person_id')
                ->constrained('persons')
                ->restrictOnDelete();

            $table
                ->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('context', 50);
            $table->string('notice_version', 100);
            $table->boolean('accepted');
            $table->timestamp('accepted_at');

            $table->timestamp('created_at')->nullable();

            $table->index(['person_id', 'context']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_consents');
    }
};