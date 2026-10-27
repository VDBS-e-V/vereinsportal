<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /*
     * Reduziert auf die bereits neu aufgebauten Module (Access,
     * Access-Governance). Die uebrigen Seeder (Registrierung,
     * Portal-Einladung, Passwort-Reset, E-Mail-Aenderung, 2FA-E-Mail,
     * Konto-Loeschung) gehoeren zu Modulen, die im Neuaufbau noch nicht
     * an der Reihe waren (Onboarding, Messaging, Offboarding) und
     * referenzieren Tabellen, die es in diesem Zwischenstand noch nicht
     * gibt. Sie kommen zurueck, sobald ihr Modul gebaut ist.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DevelopmentAdminSeeder::class,
        ]);
    }
}
