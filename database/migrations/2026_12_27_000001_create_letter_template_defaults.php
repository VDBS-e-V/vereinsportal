<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Merkt sich, welche mitgelieferten Beispielvorlagen schon geladen wurden, damit gelöschte nicht bei jedem Update wiederkommen. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_template_defaults', function (Blueprint $table): void {
            $table->string('key', 60)->primary();
            $table->timestamp('loaded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_template_defaults');
    }
};
