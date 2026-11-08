<?php

use App\Modules\Contracts\Services\ContractTexts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Spielt den Wortlaut der Vertragsvorlage (Stand 08.07.2024) als neue Fassung ein, wenn bisher nur der Entwurf (Fassung 1 nach der Klauselliste) vorhanden ist.
 * Gibt es schon eine eigene Fassung oder noch keine, bleibt alles unverändert (dann gilt der neue Standardtext ab der ersten Nutzung).
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('contract_templates')->where('key', 'service_agreement')->get();

        if ($rows->count() !== 1 || ! str_contains((string) $rows->first()->body, '§ 1 Leistung')) {
            return;
        }

        DB::table('contract_templates')->where('key', 'service_agreement')->update(['active' => false]);
        DB::table('contract_templates')->insert(['key' => 'service_agreement', 'version' => 2, 'title' => 'Dienstleistungsvertrag', 'body' => ContractTexts::defaultBody(), 'active' => true, 'created_at' => now()]);
    }

    public function down(): void
    {
        DB::table('contract_templates')->where('key', 'service_agreement')->where('version', 2)->delete();
        DB::table('contract_templates')->where('key', 'service_agreement')->where('version', 1)->update(['active' => true]);
    }
};
