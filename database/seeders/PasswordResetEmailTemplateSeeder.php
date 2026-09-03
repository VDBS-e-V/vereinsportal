<?php

namespace Database\Seeders;

use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use Illuminate\Database\Seeder;

final class PasswordResetEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = EmailTemplate::query()->firstOrCreate(
            [
                'key' => 'auth.password.reset',
            ],
            [
                'name' => 'Passwort zurücksetzen',

                /*
                 * Fail-closed:
                 * Erst eine veröffentlichte und aktivierte
                 * Vorlage darf produktiv versendet werden.
                 */
                'is_active' => false,

                'draft_subject' =>
                    'Passwort zurücksetzen',

                'draft_html' => <<<'HTML'
<p>Sie haben das Zurücksetzen Ihres Passworts angefordert.</p>
<p>
    <a href="{{ reset_url }}">
        Neues Passwort festlegen
    </a>
</p>
<p>Der Link ist bis {{ expires_at }} gültig.</p>
<p>
    Falls Sie diese Anfrage nicht gestellt haben,
    können Sie diese E-Mail ignorieren.
</p>
HTML,

                'updated_by_user_id' => null,
            ],
        );

        $placeholders = [
            [
                'key' => 'reset_url',
                'label' => 'Passwort-Reset-Link',
                'description' =>
                    'Link zum Festlegen eines neuen Passworts.',
                'example_value' =>
                    'https://my.vdb.test/passwort/zuruecksetzen/example',
                'is_required' => true,
            ],
            [
                'key' => 'expires_at',
                'label' => 'Ablaufzeitpunkt',
                'description' =>
                    'Zeitpunkt, bis zu dem der Reset-Link gültig ist.',
                'example_value' =>
                    '03.09.2026 23:30',
                'is_required' => true,
            ],
            [
                'key' => 'first_name',
                'label' => 'Vorname',
                'description' =>
                    'Vorname der betroffenen Person.',
                'example_value' => 'Erika',
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