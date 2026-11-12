<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->json('tags')->nullable();
            $table->boolean('show_author')->default(false);
            $table->string('author_name', 120)->nullable();
            $table->boolean('author_box')->default(false);
            $table->text('author_text')->nullable();
            $table->string('author_image_path')->nullable();
            $table->string('author_image_credit', 200)->nullable();
            $table->text('more_info')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn(['tags', 'show_author', 'author_name', 'author_box', 'author_text', 'author_image_path', 'author_image_credit', 'more_info']);
        });
    }
};
