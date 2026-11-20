<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Seminarablauf Phase 5a: Online-Druckplaner der Seminarmappe. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_documents', function (Blueprint $table): void {
            $table->unsignedSmallInteger('pages')->nullable();
        });

        Schema::create('seminar_print_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->unique()->constrained('seminars')->cascadeOnDelete();
            $table->json('items');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_print_plans');

        Schema::table('seminar_documents', function (Blueprint $table): void {
            $table->dropColumn('pages');
        });
    }
};
