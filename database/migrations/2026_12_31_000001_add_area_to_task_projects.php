<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Aufgaben sind kein eigener Bereich mehr: Jedes Projekt gehört zu einem Bereich und erscheint dort im Menü „Aufgaben“. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_projects', function (Blueprint $table): void {
            $table->string('area', 30)->default('geschaeftsstelle')->after('role_key')->index();
        });

        // Das Projekt „Vorstand“ gehört in den Bereich Vorstand, alle anderen zunächst zur Geschäftsstelle (im Portal änderbar)
        DB::table('task_projects')->where('slug', 'vorstand')->update(['area' => 'vorstand']);
    }

    public function down(): void
    {
        Schema::table('task_projects', function (Blueprint $table): void {
            $table->dropColumn('area');
        });
    }
};
