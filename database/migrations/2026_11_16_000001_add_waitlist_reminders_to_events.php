<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->boolean('waitlist_enabled')->default(true)->after('capacity');
        });

        Schema::table('event_registrations', function (Blueprint $table): void {
            $table->boolean('waitlisted')->default(false)->after('source');
            $table->timestamp('reminded_at')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table): void {
            $table->dropColumn(['waitlisted', 'reminded_at']);
        });
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('waitlist_enabled');
        });
    }
};
