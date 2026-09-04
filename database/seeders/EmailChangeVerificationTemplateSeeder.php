<?php

namespace Database\Seeders;

use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use Illuminate\Database\Seeder;

final class EmailChangeVerificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template =
            EmailTemplate::query()
                ->firstOrCreate(
                    [
                        'key' => 'auth.email_change.confirm_new',
                    ],
                    [
                        'name' => 'Neue E-Mail-Adresse bestätigen',
                        'is_active' => false,
                        'draft_subject' => 'Neue E-Mail-Adresse bestätigen',
                        'draft_html' => <<<'HTML'
<p>Bitte bestätigen Sie Ihre neue E-Mail-Adresse.</p>
<p>
    <a href="{{ confirmation_url }}">
        E-Mail-Adresse bestätigen
    </a>
</p>
<p>Der Link ist bis {{ expires_at }} gültig.</p>
HTML,
                        'updated_by_user_id' => null,
                    ],
                );

        $placeholders = [
            [
                'key' => 'confirmation_url',
                'label' => 'Bestätigungslink',
                'description' => 'Signierter Link zur Bestätigung der neuen E-Mail-Adresse.',
                'example_value' => 'https://my.vdb.test/email/aenderung/bestaetigen/example',
                'is_required' => true,
            ],
            [
                'key' => 'expires_at',
                'label' => 'Ablaufzeitpunkt',
                'description' => 'Zeitpunkt, bis zu dem die Bestätigung möglich ist.',
                'example_value' => '06.09.2026 22:00',
                'is_required' => true,
            ],
            [
                'key' => 'first_name',
                'label' => 'Vorname',
                'description' => 'Vorname der Person.',
                'example_value' => 'Erika',
                'is_required' => false,
            ],
            [
                'key' => 'old_email',
                'label' => 'Bisherige E-Mail-Adresse',
                'description' => 'Aktuell hinterlegte E-Mail-Adresse.',
                'example_value' => 'alt@example.test',
                'is_required' => false,
            ],
            [
                'key' => 'new_email',
                'label' => 'Neue E-Mail-Adresse',
                'description' => 'Zu bestätigende neue E-Mail-Adresse.',
                'example_value' => 'neu@example.test',
                'is_required' => false,
            ],
        ];

        foreach ($placeholders as $placeholder) {
            EmailTemplatePlaceholder::query()
                ->firstOrCreate(
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
