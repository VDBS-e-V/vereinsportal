<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_snippets', function (Blueprint $table): void {
            $table->string('category', 40)->nullable()->after('name');
            $table->unsignedInteger('sort')->default(0)->after('category');
        });

        Schema::table('mail_templates', function (Blueprint $table): void {
            $table->string('category', 40)->nullable()->after('description');
            $table->string('audience', 20)->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('mail_snippets', function (Blueprint $table): void {
            $table->dropColumn(['category', 'sort']);
        });

        Schema::table('mail_templates', function (Blueprint $table): void {
            $table->dropColumn(['category', 'audience']);
        });
    }
};
