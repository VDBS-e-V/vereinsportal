<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_assignments', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table
                ->foreignId('role_id')
                ->constrained('roles')
                ->restrictOnDelete();

            $table->string('source', 32);

            $table
                ->string('source_type', 150)
                ->nullable();

            $table
                ->unsignedBigInteger('source_id')
                ->nullable();

            $table->timestamp('starts_at');

            $table
                ->timestamp('ends_at')
                ->nullable();

            $table
                ->foreignId('granted_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('comment')->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'role_id',
                'ends_at',
            ]);

            $table->index([
                'source_type',
                'source_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_assignments');
    }
};
