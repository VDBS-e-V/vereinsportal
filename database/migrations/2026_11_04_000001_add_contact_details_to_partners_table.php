<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->string('email', 254)->nullable()->after('country_code');
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('website', 255)->nullable()->after('phone');
        });

        // Der frühere Status „inaktiv“ heißt jetzt „Pausiert“
        DB::table('partners')->where('status', 'inactive')->update(['status' => 'paused']);
    }

    public function down(): void
    {
        DB::table('partners')->whereIn('status', ['paused', 'past', 'to_approach', 'planned', 'discussion'])->update(['status' => 'inactive']);

        Schema::table('partners', function (Blueprint $table): void {
            $table->dropColumn(['email', 'phone', 'website']);
        });
    }
};
