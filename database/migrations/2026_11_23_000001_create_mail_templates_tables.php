<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mailvorlagen mit Blöcken des Mail-Builders. Auf einer bestehenden Datenbank (Stand vor der stabilen Version) gibt es die Tabellen schon:
     * dann kommen nur die fehlenden Spalten dazu, vorhandene Konten, Vorlagen und Versionen bleiben unberührt.
     */
    public function up(): void
    {
        if (! Schema::hasTable('mail_templates')) {
            Schema::create('mail_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 60)->unique();
                $table->string('kind', 20)->default('system');
                $table->string('name', 120)->nullable();
                $table->string('description', 250)->nullable();
                $table->string('category', 40)->nullable();
                $table->string('audience', 20)->nullable();
                $table->string('subject', 200);
                $table->text('body');
                $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('system_key', 80)->nullable()->unique();
                $table->unsignedBigInteger('layout_id')->nullable();
                $table->json('blocks')->nullable();
                $table->string('preheader', 200)->nullable();
                $table->text('plain_text')->nullable();
                $table->string('status', 20)->default('published');
                $table->timestamps();
            });
        } else {
            Schema::table('mail_templates', function (Blueprint $table): void {
                $has = fn (string $column): bool => Schema::hasColumn('mail_templates', $column);

                if (! $has('system_key')) {
                    $table->string('system_key', 80)->nullable()->unique();
                }

                if (! $has('layout_id')) {
                    $table->unsignedBigInteger('layout_id')->nullable();
                }

                if (! $has('blocks')) {
                    $table->json('blocks')->nullable();
                }

                if (! $has('preheader')) {
                    $table->string('preheader', 200)->nullable();
                }

                if (! $has('plain_text')) {
                    $table->text('plain_text')->nullable();
                }

                if (! $has('status')) {
                    $table->string('status', 20)->default('published');
                }
            });
        }

        if (! Schema::hasTable('mail_template_versions')) {
            Schema::create('mail_template_versions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('mail_template_id')->constrained('mail_templates')->cascadeOnDelete();
                $table->string('name', 120)->nullable();
                $table->string('subject', 200);
                $table->text('body');
                $table->foreignId('saved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('blocks')->nullable();
                $table->unsignedBigInteger('layout_id')->nullable();
                $table->string('preheader', 200)->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('mail_template_versions', function (Blueprint $table): void {
                $has = fn (string $column): bool => Schema::hasColumn('mail_template_versions', $column);

                if (! $has('blocks')) {
                    $table->json('blocks')->nullable();
                }

                if (! $has('layout_id')) {
                    $table->unsignedBigInteger('layout_id')->nullable();
                }

                if (! $has('preheader')) {
                    $table->string('preheader', 200)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_template_versions');
        Schema::dropIfExists('mail_templates');
    }
};
