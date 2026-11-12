<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailings', function (Blueprint $table): void {
            $table->string('status', 20)->default('done')->index();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->json('values')->nullable();
        });

        // Bereits versendete Schreiben gelten als abgeschlossen: alle Empfänger zugestellt, abzüglich der gemeldeten Fehler.
        DB::table('mailings')->update(['sent_count' => DB::raw('recipients_count'), 'finished_at' => DB::raw('created_at')]);

        Schema::create('mailing_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mailing_id')->constrained('mailings')->cascadeOnDelete();
            $table->string('email', 190);
            $table->string('first_name', 120)->default('');
            $table->string('last_name', 120)->default('');
            $table->string('fallback', 40)->default('Mitglied');
            $table->string('group', 30)->default('internal');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('error', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['mailing_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_deliveries');

        Schema::table('mailings', function (Blueprint $table): void {
            $table->dropColumn(['status', 'queued_at', 'started_at', 'finished_at', 'cancelled_at', 'sent_count', 'values']);
        });
    }
};
