<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Koordinationen als Datensätze. Die Schule wählt ihre Koordination aus einer Liste (partners.coordination_id).
 * Das bisherige Textfeld coordination_area wird übernommen und entfällt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('partners', function (Blueprint $table): void {
            $table->foreignId('coordination_id')->nullable()->after('city')->constrained('coordinations')->nullOnDelete();
        });

        if (Schema::hasColumn('partners', 'coordination_area')) {
            $names = DB::table('partners')->whereNotNull('coordination_area')->where('coordination_area', '!=', '')->distinct()->orderBy('coordination_area')->pluck('coordination_area');

            foreach ($names as $name) {
                $id = DB::table('coordinations')->insertGetId(['name' => (string) $name, 'active' => true, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('partners')->where('coordination_area', $name)->update(['coordination_id' => $id]);
            }

            Schema::table('partners', function (Blueprint $table): void {
                $table->dropColumn('coordination_area');
            });
        }
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->string('coordination_area', 120)->nullable()->after('city');
        });

        DB::table('partners')->whereNotNull('coordination_id')->get(['id', 'coordination_id'])->each(function (object $row): void {
            DB::table('partners')->where('id', $row->id)->update(['coordination_area' => DB::table('coordinations')->where('id', $row->coordination_id)->value('name')]);
        });

        Schema::table('partners', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('coordination_id');
        });

        Schema::dropIfExists('coordinations');
    }
};
