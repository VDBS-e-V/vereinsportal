<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Neuer Seminarablauf, Phase 1 (docs/87): Freigabe mit Vertrag oder Zusage, KVA-Freigabe, Koordinationsgebiet der Schule, neue Statuswerte. */
return new class extends Migration
{
    /** alter Status => neuer Status (nur Testbetrieb, trotzdem sauber übernommen) */
    private const MAP = ['planning' => 'in_review', 'scheduled' => 'tendered', 'confirmed' => 'team_confirmed', 'done' => 'held'];

    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->string('release_basis', 20)->nullable();
            $table->text('release_reason')->nullable();
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->date('school_contract_signed_at')->nullable();
            $table->foreignId('quote_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('quote_approved_at')->nullable();
        });

        Schema::table('partners', function (Blueprint $table): void {
            $table->string('coordination_area', 120)->nullable()->after('city');
        });

        foreach (self::MAP as $old => $new) {
            DB::table('seminars')->where('status', $old)->update(['status' => $new]);
        }

        // Altbestand: bestätigte Seminare gelten als freigegeben auf Zusage („aus Altbestand“)
        DB::table('seminars')->where('status', 'team_confirmed')->update(['release_basis' => 'commitment', 'release_reason' => 'Aus Altbestand übernommen', 'released_at' => now()]);
    }

    public function down(): void
    {
        foreach (array_flip(self::MAP) as $new => $old) {
            DB::table('seminars')->where('status', $new)->update(['status' => $old]);
        }

        Schema::table('partners', function (Blueprint $table): void {
            $table->dropColumn('coordination_area');
        });

        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('released_by_user_id');
            $table->dropConstrainedForeignId('quote_approved_by_user_id');
            $table->dropColumn(['release_basis', 'release_reason', 'released_at', 'school_contract_signed_at', 'quote_approved_at']);
        });
    }
};
