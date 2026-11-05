<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_centers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('form_settings', function (Blueprint $table): void {
            $table->string('key', 60)->primary();
            $table->string('value', 200);
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('form_counters', function (Blueprint $table): void {
            $table->string('scope', 20)->primary();
            $table->unsignedInteger('last')->default(0);
        });

        Schema::create('form_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 30)->unique();
            $table->string('type', 20)->index();
            $table->foreignId('applicant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('applicant_name', 190);
            $table->string('applicant_email', 190)->nullable();
            $table->foreignId('seminar_id')->nullable()->constrained('seminars')->nullOnDelete();
            $table->string('cost_center', 40)->nullable();
            $table->string('purpose', 300)->default('');
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->string('approval_level', 10)->default('office');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('checked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->boolean('self_checked')->default(false);
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('paid_reference', 100)->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->text('iban_encrypted')->nullable();
            $table->text('account_holder')->nullable();
            $table->json('data')->nullable();
            $table->string('rejection_note', 500)->nullable();
            $table->string('exception_reason', 300)->nullable();
            $table->foreignId('exception_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('retention_until')->nullable();
            $table->timestamps();
        });

        Schema::create('form_request_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->constrained('form_requests')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->date('date');
            $table->string('kind', 20);
            $table->string('description', 200);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->unsignedBigInteger('unit_cents')->default(0);
            $table->unsignedBigInteger('amount_cents')->default(0);
            $table->json('extra')->nullable();
        });

        Schema::create('form_request_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->constrained('form_requests')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('form_request_items')->nullOnDelete();
            $table->string('original_name', 190);
            $table->string('path', 255);
            $table->string('mime', 60);
            $table->unsignedBigInteger('size');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('form_request_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->constrained('form_requests')->cascadeOnDelete();
            $table->string('type', 30);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label', 190)->nullable();
            $table->string('comment', 500)->nullable();
            $table->timestamp('occurred_at');
        });
    }

    public function down(): void
    {
        foreach (['form_request_events', 'form_request_files', 'form_request_items', 'form_requests', 'form_counters', 'form_settings', 'cost_centers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
