<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kostenvoranschläge sind änderbar. Jede Änderung ist eine neue Fassung; alle Fassungen bleiben als unveränderliche Schnappschüsse
 * in seminar_quote_versions erhalten. seminar_quotes trägt den aktuellen Stand und die Nummer der aktuellen Fassung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_quotes', function (Blueprint $table): void {
            $table->unsignedSmallInteger('version')->default(1)->after('price_list_id');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });

        Schema::create('seminar_quote_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained('seminar_quotes')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->foreignId('price_list_id')->constrained('seminar_price_lists')->restrictOnDelete();
            $table->json('positions');
            $table->unsignedInteger('total_cents');
            $table->string('comment', 500)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['quote_id', 'version']);
        });

        // Bestehende Kostenvoranschläge sind Fassung 1
        foreach (DB::table('seminar_quotes')->get() as $quote) {
            DB::table('seminar_quote_versions')->insert([
                'quote_id' => $quote->id,
                'version' => 1,
                'price_list_id' => $quote->price_list_id,
                'positions' => $quote->positions,
                'total_cents' => $quote->total_cents,
                'comment' => null,
                'created_by_user_id' => $quote->created_by_user_id,
                'created_at' => $quote->created_at,
            ]);

            DB::table('seminar_quotes')->where('id', $quote->id)->update(['updated_at' => $quote->created_at]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_quote_versions');

        Schema::table('seminar_quotes', function (Blueprint $table): void {
            $table->dropColumn(['version', 'updated_at']);
        });
    }
};
