<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_links', function (Blueprint $table): void {
            $table->id();
            $table->string('role_key', 40)->index();
            $table->string('label', 60);
            $table->string('url', 255);
            $table->unsignedTinyInteger('priority')->default(1);
            $table->timestamps();
        });

        // Ausgangsbestand: je Rolle bis zu drei Links auf vorhandene Seiten; im Portal änderbar.
        $defaults = [
            'member' => [['Meine Mitgliedschaft', 'member.membership'], ['Mitgliederbereich', 'member.home'], ['Meine Aufgaben', 'tasks.mine']],
            'board_member' => [['Vorstandssitzungen', 'board.meetings.index'], ['Mitgliedschaften', 'membership.index'], ['Übersicht Vorstand', 'board.overview']],
            'team' => [['Bereich Teamende', 'team.home'], ['Meine Aufgaben', 'tasks.mine'], ['Mein Profil', 'account.profile']],
            'administration_staff' => [['Geschäftsstelle', 'office.home'], ['Mitgliedschaften', 'membership.index'], ['Seminare', 'seminars.index']],
            'education_coordination' => [['Bildungsreferenten', 'education.overview'], ['Seminare', 'seminars.index'], ['Meine Aufgaben', 'tasks.mine']],
            'coordination' => [['Koordination', 'coordination.home'], ['Seminare', 'seminars.index'], ['Meine Aufgaben', 'tasks.mine']],
            'administration' => [['Admins', 'admins.home'], ['Nutzer', 'admin.users.index'], ['FAQ verwalten', 'faq-admin.index']],
            'employee' => [['Bereich Mitarbeitende', 'employee.home'], ['Meine Aufgaben', 'tasks.mine'], ['Kontoeinstellungen', 'account.home']],
        ];

        $now = now();
        $rows = [];

        foreach ($defaults as $role => $links) {
            foreach ($links as $index => [$label, $name]) {
                if (! Route::has($name)) {
                    continue;
                }

                $rows[] = ['role_key' => $role, 'label' => $label, 'url' => route($name, [], false), 'priority' => $index + 1, 'created_at' => $now, 'updated_at' => $now];
            }
        }

        if ($rows !== []) {
            DB::table('role_links')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_links');
    }
};
