<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('navigation_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
            $table->string('ref')->nullable();
            $table->string('parent_ref')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('navigation_items', function (Blueprint $table): void {
            $table->dropColumn(['ref', 'parent_ref']);
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->cascadeOnDelete();
        });
    }
};
