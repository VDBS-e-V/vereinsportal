<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('subject', 200);
            $table->text('body');
            $table->string('audience', 20);
            $table->unsignedInteger('recipients_count')->default(0);
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('event_registrations', function (Blueprint $table): void {
            $table->foreignId('partner_id')->nullable()->after('event_id')->constrained('partners')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('partner_id');
        });
        Schema::dropIfExists('event_messages');
    }
};
