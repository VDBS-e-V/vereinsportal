<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->string('author_name', 120)->nullable();
            $table->text('author_text')->nullable();
            $table->boolean('author_use_photo')->default(false);
            $table->string('author_photo_credit', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropColumn(['author_name', 'author_text', 'author_use_photo', 'author_photo_credit']);
        });
    }
};
