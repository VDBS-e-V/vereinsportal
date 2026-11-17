<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_hidden', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->timestamps();
        });

        // Erste Produktionsversion: nur bestätigte Bereiche. Die übrigen lassen sich in der Verwaltung (Admins › Navigation) einschalten.
        if (app()->environment('production')) {
            foreach (['mitarbeitende', 'schulen', 'geschaeftsstelle', 'bildungsreferenten'] as $area) {
                DB::table('navigation_hidden')->insert(['key' => 'area:'.$area, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_hidden');
    }
};
