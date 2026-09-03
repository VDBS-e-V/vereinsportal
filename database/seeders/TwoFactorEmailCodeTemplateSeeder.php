<?php

namespace Database\Seeders;

use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use Illuminate\Database\Seeder;

final class TwoFactorEmailCodeTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template =
            EmailTemplate::query()
                ->firstOrCreate(
                    [
                        'key' =>
                            'auth.two_factor.email_code',
                    ],
                    [
                        'name' =>
                            'Zwei-Faktor-Code',
                        'is_active' => false,
                        'draft_subject' =>
                            'Ihr Sicherheitscode',
                        'draft_html' => <<<'HTML'
<p>Ihr Sicherheitscode lautet:</p>
<p><strong>{{ code }}</strong></p>
<p>Der Code ist {{ expires_in_minutes }} Minuten gültig.</p>
HTML,
                        'updated_by_user_id' =>
                            null,
                    ],
                );

        $placeholders = [
            [
                'key' => 'code',
                'label' => 'Sicherheitscode',
                'description' =>
                    'Einmaliger Code für die Zwei-Faktor-Anmeldung.',
                'example_value' => '123456',
                'is_required' => true,
            ],
            [
                'key' => 'expires_in_minutes',
                'label' => 'Gültigkeit in Minuten',
                'description' =>
                    'Gültigkeitsdauer des Sicherheitscodes.',
                'example_value' => '15',
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
                'key' => 'support_email',
                'label' => 'Support-E-Mail',
                'description' =>
                    'Kontaktadresse bei Sicherheitsfragen.',
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