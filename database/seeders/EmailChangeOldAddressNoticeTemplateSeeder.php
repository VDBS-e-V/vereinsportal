<?php

namespace Database\Seeders;

use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use Illuminate\Database\Seeder;

final class EmailChangeOldAddressNoticeTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template =
            EmailTemplate::query()
                ->firstOrCreate(
                    [
                        'key' =>
                            'auth.email_change.old_address_notice',
                    ],
                    [
                        'name' =>
                            'Sicherheitshinweis nach E-Mail-Änderung',
                        'is_active' => false,
                        'draft_subject' =>
                            'Ihre E-Mail-Adresse wurde geändert',
                        'draft_html' => <<<'HTML'
<p>Die E-Mail-Adresse Ihres Kontos wurde geändert.</p>
<p>
    Falls Sie diese Änderung nicht veranlasst haben,
    öffnen Sie bitte den folgenden Sicherheitshinweis:
</p>
<p>
    <a href="{{ security_url }}">
        Sicherheitshinweis öffnen
    </a>
</p>
HTML,
                        'updated_by_user_id' =>
                            null,
                    ],
                );

        $placeholders = [
            [
                'key' => 'security_url',
                'label' => 'Sicherheitslink',
                'description' =>
                    'Signierter Link zum Sicherheitshinweis.',
                'example_value' =>
                    'https://my.vdb.test/email/aenderung/sicherheit/example',
                'is_required' => true,
            ],
            [
                'key' => 'first_name',
                'label' => 'Vorname',
                'description' =>
                    'Vorname der Person.',
                'example_value' => 'Erika',
                'is_required' => false,
            ],
            [
                'key' => 'old_email',
                'label' => 'Bisherige E-Mail-Adresse',
                'description' =>
                    'E-Mail-Adresse vor der Änderung.',
                'example_value' =>
                    'alt@example.test',
                'is_required' => false,
            ],
            [
                'key' => 'new_email',
                'label' => 'Neue E-Mail-Adresse',
                'description' =>
                    'Neu bestätigte E-Mail-Adresse.',
                'example_value' =>
                    'neu@example.test',
                'is_required' => false,
            ],
            [
                'key' => 'support_email',
                'label' => 'Support-E-Mail',
                'description' =>
                    'Kontaktadresse für Sicherheitsfragen.',
                'example_value' =>
                    'support@example.test',
                'is_required' => false,
            ],
        ];

        foreach ($placeholders as $placeholder) {
            EmailTemplatePlaceholder::query()
                ->firstOrCreate(
                    [
                        'email_template_id' =>
                            $template->id,
                        'key' =>
                            $placeholder['key'],
                    ],
                    [
                        'label' =>
                            $placeholder['label'],
                        'description' =>
                            $placeholder['description'],
                        'example_value' =>
                            $placeholder['example_value'],
                        'is_required' =>
                            $placeholder['is_required'],
                        'is_active' => true,
                    ],
                );
        }
    }
}