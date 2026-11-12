<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailings', function (Blueprint $table): void {
            $table->timestamp('send_at')->nullable()->index();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('payload')->nullable();
            $table->string('rejection_note', 500)->nullable();
            $table->string('sender_area', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mailings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropColumn(['send_at', 'approved_at', 'payload', 'rejection_note', 'sender_area']);
        });
    }
};
