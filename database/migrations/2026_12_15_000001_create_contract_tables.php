<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 40)->index();
            $table->unsignedInteger('version');
            $table->string('title', 150);
            $table->longText('body');
            $table->boolean('active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['key', 'version']);
        });

        Schema::create('contract_settings', function (Blueprint $table): void {
            $table->string('key', 60)->primary();
            $table->string('value', 300)->default('');
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('contract_counters', function (Blueprint $table): void {
            $table->string('scope', 20)->primary();
            $table->unsignedInteger('last')->default(0);
        });

        Schema::create('contractor_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('tax_number')->nullable();
            $table->string('vat_mode', 10)->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->string('bic', 20)->nullable();
            $table->text('iban')->nullable();
            $table->string('account_holder', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('seminar_contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('seminar_applications')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('contract_templates')->nullOnDelete();
            $table->unsignedInteger('template_version')->default(1);
            $table->longText('data');
            $table->string('status', 15)->default('created')->index();
            $table->foreignId('signed_document_id')->nullable()->constrained('seminar_documents')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->date('retention_until')->nullable()->index();
            $table->timestamps();

            $table->index(['seminar_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_contracts');
        Schema::dropIfExists('contractor_profiles');
        Schema::dropIfExists('contract_counters');
        Schema::dropIfExists('contract_settings');
        Schema::dropIfExists('contract_templates');
    }
};
