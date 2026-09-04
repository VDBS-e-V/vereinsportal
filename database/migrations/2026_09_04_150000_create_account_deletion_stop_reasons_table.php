<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletion_stop_reasons', function (Blueprint $table): void {
            $table->id();

            $table
                ->string('key', 64)
                ->unique();

            $table->string('label', 150);

            $table
                ->boolean('requires_comment')
                ->default(false);

            $table
                ->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_stop_reasons');
    }
};
