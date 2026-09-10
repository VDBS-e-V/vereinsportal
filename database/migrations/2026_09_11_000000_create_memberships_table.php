<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('person_id')
                ->constrained('persons')
                ->restrictOnDelete();

            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamps();

            $table->index([
                'person_id',
                'starts_on',
            ]);

            $table->index([
                'person_id',
                'ends_on',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
