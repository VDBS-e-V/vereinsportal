<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailings', function (Blueprint $table): void {
            $table->json('groups')->nullable();
        });

        Schema::table('mail_templates', function (Blueprint $table): void {
            $table->string('kind', 20)->default('system');
            $table->string('name', 120)->nullable();
            $table->string('description', 250)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mailings', function (Blueprint $table): void {
            $table->dropColumn('groups');
        });

        Schema::table('mail_templates', function (Blueprint $table): void {
            $table->dropColumn(['kind', 'name', 'description']);
        });
    }
};
