<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'registration_requests',
            function (Blueprint $table): void {
                $table
                    ->timestamp('verification_sent_at')
                    ->nullable()
                    ->after('verification_expires_at');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'registration_requests',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'verification_sent_at'
                );
            }
        );
    }
};
