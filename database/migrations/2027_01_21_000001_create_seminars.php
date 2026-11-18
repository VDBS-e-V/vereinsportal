<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Seminare: Anfrage der Schule, Termine, Verlauf und der Nummernkreis. Weitere Angaben kommen mit den späteren Phasen des Ablaufs. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminars', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 20)->nullable()->unique();
            $table->string('title', 200);
            $table->string('status', 20)->index();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('coordination_id')->nullable()->constrained('coordinations')->nullOnDelete();
            $table->string('school_year', 9)->nullable();
            $table->string('format', 120)->nullable();
            $table->unsignedSmallInteger('participants')->nullable();
            $table->string('desired_period', 200)->nullable();
            $table->text('request_note')->nullable();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_email', 254)->nullable();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('seminar_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('seminar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label', 150)->nullable();
            $table->string('type', 40);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->text('comment')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('seminar_counters', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_counters');
        Schema::dropIfExists('seminar_events');
        Schema::dropIfExists('seminar_sessions');
        Schema::dropIfExists('seminars');
    }
};
