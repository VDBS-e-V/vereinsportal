<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Kostenvoranschlag (KVA): Preisliste, Textbausteine, Versionen je Seminar mit Positionen. Beträge in Cent. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_price_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('unit', 30)->nullable();
            $table->unsignedInteger('unit_price_cents');
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quote_texts', function (Blueprint $table): void {
            $table->string('key', 30)->primary();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('seminar_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('status', 12)->index();
            $table->decimal('vat_percent', 4, 1)->default(0);
            $table->text('intro')->nullable();
            $table->text('outro')->nullable();
            $table->unsignedInteger('net_cents')->default(0);
            $table->unsignedInteger('vat_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
            $table->unique(['seminar_id', 'version']);
        });

        Schema::create('seminar_quote_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_quote_id')->constrained('seminar_quotes')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('description', 300);
            $table->decimal('quantity', 8, 2);
            $table->string('unit', 30)->nullable();
            $table->unsignedInteger('unit_price_cents');
        });

        DB::table('quote_texts')->insert([
            ['key' => 'intro', 'body' => 'vielen Dank für Ihre Anfrage. Gern bieten wir Ihnen das Seminar zu den folgenden Konditionen an.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'outro', 'body' => 'Dieser Kostenvoranschlag ist unverbindlich. Verbindlich wird das Seminar mit dem unterschriebenen Vertrag. Bei Fragen melden wir uns gern.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_quote_lines');
        Schema::dropIfExists('seminar_quotes');
        Schema::dropIfExists('quote_texts');
        Schema::dropIfExists('quote_price_items');
    }
};
