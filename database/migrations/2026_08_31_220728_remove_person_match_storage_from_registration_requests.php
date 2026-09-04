<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_requests', function (Blueprint $table): void {
            $table->dropForeign(['matched_person_id']);

            $table->dropColumn([
                'matched_person_id',
                'match_count',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('registration_requests', function (Blueprint $table): void {
            $table
                ->foreignId('matched_person_id')
                ->nullable()
                ->constrained('persons')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('match_count');
        });
    }
};
