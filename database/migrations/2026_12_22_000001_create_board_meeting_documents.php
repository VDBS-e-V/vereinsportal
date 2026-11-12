<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_meeting_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_meeting_id')->constrained('board_meetings')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('original_name', 190);
            $table->string('path', 255);
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('board_meetings', function (Blueprint $table): void {
            $table->timestamp('invited_at')->nullable()->after('minutes_approved_on');
        });
    }

    public function down(): void
    {
        Schema::table('board_meetings', function (Blueprint $table): void {
            $table->dropColumn('invited_at');
        });
        Schema::dropIfExists('board_meeting_documents');
    }
};
