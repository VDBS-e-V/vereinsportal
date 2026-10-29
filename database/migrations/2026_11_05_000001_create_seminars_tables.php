<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_price_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 120);
            $table->date('valid_from');
            $table->json('prices');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('seminars', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 200);
            $table->string('type', 32);
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->string('status', 32)->default('request');
            $table->string('grade_level', 100)->nullable();
            $table->unsignedSmallInteger('expected_participants')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });

        Schema::create('seminar_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('location', 200)->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['seminar_id', 'starts_at']);
        });

        Schema::create('seminar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->string('type', 32);
            $table->dateTime('occurred_at');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label', 200)->nullable();
            $table->text('comment')->nullable();
            $table->json('data')->nullable();

            $table->index(['seminar_id', 'occurred_at']);
        });

        Schema::create('seminar_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->foreignId('price_list_id')->constrained('seminar_price_lists')->restrictOnDelete();
            $table->json('inputs');
            $table->json('lines');
            $table->unsignedInteger('total_cents');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['seminar_id', 'version']);
        });

        // Preisstand der Preisberechnungstabelle (Oktober 2025), Beträge in Cent
        DB::table('seminar_price_lists')->insert([
            'label' => 'Preisstand Oktober 2025',
            'valid_from' => '2025-10-01',
            'prices' => json_encode([
                'trip_inside' => 1000, 'trip_outside' => 4500, 'orga_day' => 2000, 'club_fee' => 6500,
                'honorarium_2_5' => 10000, 'honorarium_4_5' => 13500, 'honorarium_6' => 17000, 'honorarium_full' => 20000,
                'leader_short' => 3000, 'leader_long' => 5000,
                'material' => 3200, 'material_extra' => 400, 'board' => 4100,
                'material_trip_inside' => 3500, 'material_trip_outside' => 10500,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_quotes');
        Schema::dropIfExists('seminar_events');
        Schema::dropIfExists('seminar_sessions');
        Schema::dropIfExists('seminars');
        Schema::dropIfExists('seminar_price_lists');
    }
};
