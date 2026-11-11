<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_unsubscribes', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 190);
            $table->string('topic', 30);
            $table->timestamps();

            $table->unique(['email', 'topic']);
        });

        Schema::create('mail_suppressions', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 190)->index();
            $table->string('reason', 20);
            $table->string('source', 40)->nullable();
            $table->string('note', 250)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('mailings', function (Blueprint $table): void {
            $table->boolean('mandatory')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('mailings', function (Blueprint $table): void {
            $table->dropColumn('mandatory');
        });

        Schema::dropIfExists('mail_suppressions');
        Schema::dropIfExists('mail_unsubscribes');
    }
};
