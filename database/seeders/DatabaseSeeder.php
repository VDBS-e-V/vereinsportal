<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            RegistrationVerificationEmailTemplateSeeder::class,
            PasswordResetEmailTemplateSeeder::class,
            EmailChangeVerificationTemplateSeeder::class,
            EmailChangeOldAddressNoticeTemplateSeeder::class,
            TwoFactorEmailCodeTemplateSeeder::class,
            AccountDeletionEmailTemplateSeeder::class,
        ]);
    }
}
