<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_quotes', function (Blueprint $table): void {
            $table->string('item_title', 150)->nullable()->after('total_cents');
            $table->text('item_content')->nullable()->after('item_title');
        });
    }

    public function down(): void
    {
        Schema::table('seminar_quotes', function (Blueprint $table): void {
            $table->dropColumn(['item_title', 'item_content']);
        });
    }
};
