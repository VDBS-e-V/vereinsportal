<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kostenvoranschläge gehören jetzt zu einer Schule und bestehen aus mehreren Positionen (Produkt mit Stückzahl). Das Seminar ist nur noch
 * eine optionale Verknüpfung. Bestehende Kostenvoranschläge werden zu einem KVA mit einer Position.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_quotes', function (Blueprint $table): void {
            $table->unsignedBigInteger('partner_id')->nullable()->after('id');
            $table->json('positions')->nullable()->after('price_list_id');
        });

        foreach (DB::table('seminar_quotes')->get() as $quote) {
            $seminar = DB::table('seminars')->where('id', $quote->seminar_id)->first();
            $inputs = json_decode((string) $quote->inputs, true) ?: [];

            DB::table('seminar_quotes')->where('id', $quote->id)->update([
                'partner_id' => $seminar?->partner_id,
                'positions' => json_encode([[
                    'product' => $seminar?->type ?? 'sv_days',
                    'inputs' => $inputs,
                    'lines' => json_decode((string) $quote->lines, true) ?: [],
                    'total_cents' => (int) $quote->total_cents,
                    'title' => (string) ($quote->item_title ?? ''),
                    'content' => (string) ($quote->item_content ?? ''),
                ]], JSON_UNESCAPED_UNICODE),
            ]);
        }

        Schema::table('seminar_quotes', function (Blueprint $table): void {
            $table->dropForeign(['seminar_id']);
            $table->dropUnique(['seminar_id', 'version']);
            $table->dropColumn(['version', 'inputs', 'lines', 'item_title', 'item_content']);
        });

        Schema::table('seminar_quotes', function (Blueprint $table): void {
            $table->unsignedBigInteger('seminar_id')->nullable()->change();
            $table->unsignedBigInteger('partner_id')->nullable(false)->change();
            $table->json('positions')->nullable(false)->change();
        });

        Schema::table('seminar_quotes', function (Blueprint $table): void {
            $table->foreign('seminar_id')->references('id')->on('seminars')->nullOnDelete();
            $table->foreign('partner_id')->references('id')->on('partners')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Diese Migration lässt sich nicht zurücknehmen.');
    }
};
