<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Legt die neuen Systemrollen an, damit die Livedatenbank sie ohne Seeder-Lauf bekommt (wiederholbar). */
    public function up(): void
    {
        foreach (['employee' => 'Mitarbeitende', 'managing_director' => 'Geschaeftsfuehrung', 'treasury' => 'Kassenfuehrung'] as $key => $name) {
            if (! DB::table('roles')->where('key', $key)->exists()) {
                DB::table('roles')->insert(['key' => $key, 'name' => $name, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('roles')->whereIn('key', ['employee', 'managing_director', 'treasury'])->pluck('id');
        DB::table('role_assignments')->whereIn('role_id', $ids)->delete();
        DB::table('roles')->whereIn('id', $ids)->delete();
    }
};
