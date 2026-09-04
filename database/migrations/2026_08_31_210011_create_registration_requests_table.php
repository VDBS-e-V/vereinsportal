<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_requests', function (Blueprint $table): void {
            $table->id();

            $table->char('public_id', 26)->unique();

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('birth_date');
            $table->string('email', 254);

            $table->string('password');

            $table->string('privacy_notice_version', 100);
            $table->timestamp('consented_at');

            $table
                ->foreignId('matched_person_id')
                ->nullable()
                ->constrained('persons')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('match_count');

            $table->string('verification_recipient_email', 254);

            $table
                ->unsignedInteger('verification_version')
                ->default(1);

            $table->timestamp('verification_expires_at');
            $table->timestamp('expires_at');

            $table->string('status', 32);

            $table->timestamps();

            $table->index('email');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_requests');
    }
};
