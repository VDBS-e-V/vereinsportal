<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('privacy_consents')
            ->whereNull('created_at')
            ->update([
                'created_at' => now(),
            ]);

        Schema::table('privacy_consents', function (Blueprint $table): void {
            $table
                ->timestamp('created_at')
                ->nullable(false)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('privacy_consents', function (Blueprint $table): void {
            $table
                ->timestamp('created_at')
                ->nullable()
                ->change();
        });
    }
};