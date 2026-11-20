<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Rückmeldung der Schule zu einem durchgeführten Seminar: Bewertungen (1 bis 5) und Freitext, eine je Seminar. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->unique()->constrained('seminars')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('ratings');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_feedback');
    }
};
