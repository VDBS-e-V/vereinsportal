<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DevelopmentAdminSeeder::class,
            RegistrationVerificationEmailTemplateSeeder::class,
            PortalInvitationEmailTemplateSeeder::class,
            PasswordResetEmailTemplateSeeder::class,
            EmailChangeVerificationTemplateSeeder::class,
            EmailChangeOldAddressNoticeTemplateSeeder::class,
            TwoFactorEmailCodeTemplateSeeder::class,
            AccountDeletionEmailTemplateSeeder::class,
        ]);
    }
}
