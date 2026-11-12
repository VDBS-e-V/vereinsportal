<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('location', 200)->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamp('registration_deadline')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('event_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('location', 200)->nullable();
            $table->string('notes', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('event_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('school', 200);
            $table->string('class_name', 40);
            $table->date('birth_date');
            $table->string('email', 254);
            $table->string('ticket_code', 12)->unique();
            $table->string('source', 10)->default('self');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['event_id', 'email']);
            $table->index('verified_at');
        });

        Schema::create('event_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('marked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['registration_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendances');
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('event_sessions');
        Schema::dropIfExists('events');
    }
};
