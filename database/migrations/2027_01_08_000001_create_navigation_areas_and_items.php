<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_areas', function (Blueprint $table): void {
            $table->id();
            $table->string('area')->unique();
            $table->unsignedSmallInteger('sort')->nullable();
            $table->string('label', 60)->nullable();
            $table->boolean('show_header')->default(true);
            $table->boolean('show_lists')->default(true);
            $table->json('roles')->nullable();
            $table->timestamps();
        });

        Schema::create('navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->string('area')->index();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->cascadeOnDelete();
            $table->string('page_key')->nullable();
            $table->string('label', 60)->nullable();
            $table->string('url')->nullable();
            $table->unsignedSmallInteger('sort')->nullable();
            $table->boolean('visible')->default(true);
            $table->timestamps();
        });

        // Übernimmt die ausgeblendeten Bereiche und Seiten der ersten Fassung
        if (Schema::hasTable('navigation_hidden')) {
            foreach (DB::table('navigation_hidden')->pluck('key') as $key) {
                $parts = explode(':', (string) $key, 3);

                if ($parts[0] === 'area' && isset($parts[1])) {
                    DB::table('navigation_areas')->insert(['area' => $parts[1], 'show_header' => false, 'show_lists' => false, 'created_at' => now(), 'updated_at' => now()]);
                } elseif ($parts[0] === 'page' && count($parts) === 3) {
                    DB::table('navigation_items')->insert(['area' => $parts[1], 'page_key' => $parts[2], 'visible' => false, 'created_at' => now(), 'updated_at' => now()]);
                }
            }

            Schema::drop('navigation_hidden');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_areas');
    }
};
