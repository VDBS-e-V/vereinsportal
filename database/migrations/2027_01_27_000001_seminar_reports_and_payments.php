<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Durchführung, Bericht, Abschluss und die Zahlungsaufträge aus den hochgeladenen Verträgen der Teamenden. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->timestamp('held_at')->nullable()->after('tender_reminded_at');
            $table->date('report_due_on')->nullable()->after('held_at');
            $table->timestamp('reported_at')->nullable()->after('report_due_on');
            $table->string('results_url', 500)->nullable()->after('reported_at');
            $table->date('results_expires_on')->nullable()->after('results_url');
            $table->timestamp('report_sent_at')->nullable()->after('results_expires_on');
            $table->timestamp('completed_at')->nullable()->after('report_sent_at');
            $table->timestamp('report_reminded_at')->nullable()->after('completed_at');
            $table->timestamp('report_overdue_notified_at')->nullable()->after('report_reminded_at');
        });

        Schema::create('payment_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seminar_document_id')->nullable()->constrained('seminar_documents')->nullOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('status', 10)->default('open')->index();
            $table->text('note')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['seminar_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');

        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropColumn(['held_at', 'report_due_on', 'reported_at', 'results_url', 'results_expires_on', 'report_sent_at', 'completed_at', 'report_reminded_at', 'report_overdue_notified_at']);
        });
    }
};
