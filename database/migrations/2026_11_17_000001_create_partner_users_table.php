<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('partner_contacts')->nullOnDelete();
            $table->string('name', 150);
            $table->timestamps();

            $table->index('partner_id');
        });

        // Die neue Systemrolle „Schule“ gibt es auch ohne erneutes Seeden.
        DB::table('roles')->updateOrInsert(['key' => 'school'], ['name' => 'Schule', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_users');
    }
};
