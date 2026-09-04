<?php

namespace Database\Seeders;

use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use Illuminate\Database\Seeder;

final class RegistrationVerificationEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = EmailTemplate::query()->firstOrCreate(
            [
                'key' => 'auth.registration.verify',
            ],
            [
                'name' => 'Registrierung bestätigen',

                /*
                 * Fail-closed:
                 * Erst nach einer echten Veröffentlichung durch
                 * Administration wird die Vorlage aktiviert.
                 */
                'is_active' => false,

                'draft_subject' => 'Registrierung bestätigen',

                'draft_html' => <<<'HTML'
<p>Bitte bestätigen Sie Ihre Registrierung.</p>
<p>
    <a href="{{ verification_url }}">
        Registrierung bestätigen
    </a>
</p>
<p>Der Link ist bis {{ expires_at }} gültig.</p>
HTML,

                'updated_by_user_id' => null,
            ],
        );

        $placeholders = [
            [
                'key' => 'verification_url',
                'label' => 'Verifikationslink',
                'description' => 'Signierter Link zum Abschluss der Registrierung.',
                'example_value' => 'https://my.vdb.test/identity/registration/verify/example/1',
                'is_required' => true,
            ],
            [
                'key' => 'expires_at',
                'label' => 'Ablaufzeitpunkt',
                'description' => 'Zeitpunkt, bis zu dem der Verifikationslink gültig ist.',
                'example_value' => '04.09.2026 12:00',
                'is_required' => true,
            ],
            [
                'key' => 'first_name',
                'label' => 'Vorname',
                'description' => 'Vorname der registrierenden Person.',
                'example_value' => 'Erika',
                'is_required' => false,
            ],
            [
                'key' => 'privacy_notice_version',
                'label' => 'Datenschutzhinweis-Version',
                'description' => 'Version des bei der Registrierung akzeptierten Datenschutzhinweises.',
                'example_value' => 'privacy-v1',
                'is_required' => false,
            ],
            [
                'key' => 'support_email',
                'label' => 'Support-E-Mail',
                'description' => 'Kontaktadresse für Rückfragen zur Registrierung.',
                'example_value' => 'support@example.test',
                'is_required' => false,
            ],
        ];

        foreach ($placeholders as $placeholder) {
            EmailTemplatePlaceholder::query()->firstOrCreate(
                [
                    'email_template_id' => $template->id,
                    'key' => $placeholder['key'],
                ],
                [
                    'label' => $placeholder['label'],
                    'description' => $placeholder['description'],
                    'example_value' => $placeholder['example_value'],
                    'is_required' => $placeholder['is_required'],
                    'is_active' => true,
                ],
            );
        }
    }
}
