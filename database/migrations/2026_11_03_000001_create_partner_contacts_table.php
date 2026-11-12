<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Modul: Partners.
     *
     * Mehrere Ansprechpersonen je Partner (Schule): eigene Tabelle mit Name, Funktion, E-Mail, Telefon und optionaler Verknüpfung
     * zu einer bekannten Person. Die bisherigen Einzelfelder an partners (contact_*) werden übernommen und entfernt.
     */
    public function up(): void
    {
        Schema::create('partner_contacts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();

            $table->string('name', 150);
            $table->string('role', 100)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('phone', 50)->nullable();

            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->index(['partner_id', 'is_primary']);
        });

        DB::table('partners')
            ->where(fn ($query) => $query->whereNotNull('contact_name')->orWhereNotNull('contact_email')->orWhereNotNull('contact_phone')->orWhereNotNull('contact_person_id'))
            ->orderBy('id')
            ->each(function (object $partner): void {
                DB::table('partner_contacts')->insert([
                    'partner_id' => $partner->id,
                    'person_id' => $partner->contact_person_id,
                    'name' => $partner->contact_name ?: 'Ansprechperson',
                    'email' => $partner->contact_email,
                    'phone' => $partner->contact_phone,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('partners', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('contact_person_id');
            $table->dropColumn(['contact_name', 'contact_email', 'contact_phone']);
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->foreignId('contact_person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_email', 254)->nullable();
            $table->string('contact_phone', 50)->nullable();
        });

        DB::table('partner_contacts')->where('is_primary', true)->orderBy('id')->each(function (object $contact): void {
            DB::table('partners')->where('id', $contact->partner_id)->update([
                'contact_person_id' => $contact->person_id,
                'contact_name' => $contact->name,
                'contact_email' => $contact->email,
                'contact_phone' => $contact->phone,
            ]);
        });

        Schema::dropIfExists('partner_contacts');
    }
};
