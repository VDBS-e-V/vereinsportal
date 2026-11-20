<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Dokumente zu einem Seminar (privat gespeichert) und die Freigabe mit Schulvertrag oder auf verbindliche Zusage. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->cascadeOnDelete();
            $table->string('kind', 20)->index();
            $table->string('title', 200);
            $table->string('path', 300);
            $table->string('original_name', 200);
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('seminars', function (Blueprint $table): void {
            $table->string('release_basis', 12)->nullable()->after('contact_email');
            $table->text('release_reason')->nullable()->after('release_basis');
            $table->timestamp('released_at')->nullable()->after('release_reason');
            $table->foreignId('released_by_user_id')->nullable()->after('released_at')->constrained('users')->nullOnDelete();
            $table->date('school_contract_signed_on')->nullable()->after('released_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('released_by_user_id');
            $table->dropColumn(['release_basis', 'release_reason', 'released_at', 'school_contract_signed_on']);
        });

        Schema::dropIfExists('seminar_documents');
    }
};
