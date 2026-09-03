<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_template_placeholders', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('email_template_id')
                ->constrained('email_templates')
                ->restrictOnDelete();

            $table->string('key', 100);
            $table->string('label', 150);
            $table->text('description');
            $table->text('example_value')->nullable();
            $table->boolean('is_required');
            $table->boolean('is_active');

            $table->unique([
                'email_template_id',
                'key',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_placeholders');
    }
};