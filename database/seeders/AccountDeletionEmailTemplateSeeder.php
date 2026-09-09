<?php

namespace Database\Seeders;

use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use Illuminate\Database\Seeder;

final class AccountDeletionEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'account.deletion.confirm_request',
                'name' => 'Kontolöschung bestätigen',
                'draft_subject' => 'Kontolöschung bestätigen',
                'draft_html' => <<<'HTML'
<p>Bitte bestätigen Sie die Löschung Ihres Kontos.</p>
<p>
    <a href="{{ confirmation_url }}">
        Kontolöschung bestätigen
    </a>
</p>
<p>Der Link ist bis {{ expires_at }} gültig.</p>
HTML,
                'placeholders' => [
                    [
                        'key' => 'confirmation_url',
                        'label' => 'Bestätigungslink',
                        'description' => 'Signierter Link zur Bestätigung der Kontolöschung.',
                        'example_value' => 'https://my.vdb.test/identity/account-deletion/confirm/example',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'expires_at',
                        'label' => 'Ablaufzeitpunkt',
                        'description' => 'Zeitpunkt, bis zu dem der Bestätigungslink gültig ist.',
                        'example_value' => '09.09.2026 12:00',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'first_name',
                        'label' => 'Vorname',
                        'description' => 'Vorname der betroffenen Person.',
                        'example_value' => 'Erika',
                        'is_required' => false,
                    ],
                    [
                        'key' => 'support_email',
                        'label' => 'Support-E-Mail',
                        'description' => 'Kontaktadresse für Rückfragen zur Kontolöschung.',
                        'example_value' => 'support@example.test',
                        'is_required' => false,
                    ],
                ],
            ],
            [
                'key' => 'account.deletion.withdraw_available',
                'name' => 'Widerruf der Kontolöschung möglich',
                'draft_subject' => 'Kontolöschung bestätigt – Widerruf möglich',
                'draft_html' => <<<'HTML'
<p>Die Kontolöschung wurde bestätigt.</p>
<p>
    Sie können die Löschung bis {{ withdraw_until }} widerrufen.
</p>
<p>
    <a href="{{ withdraw_url }}">
        Kontolöschung widerrufen
    </a>
</p>
HTML,
                'placeholders' => [
                    [
                        'key' => 'withdraw_url',
                        'label' => 'Widerrufslink',
                        'description' => 'Signierter Link zum Widerruf der Kontolöschung.',
                        'example_value' => 'https://my.vdb.test/identity/account-deletion/withdraw/example',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'withdraw_until',
                        'label' => 'Widerrufsfrist',
                        'description' => 'Zeitpunkt, bis zu dem die Kontolöschung widerrufen werden kann.',
                        'example_value' => '11.09.2026 12:00',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'first_name',
                        'label' => 'Vorname',
                        'description' => 'Vorname der betroffenen Person.',
                        'example_value' => 'Erika',
                        'is_required' => false,
                    ],
                ],
            ],
            [
                'key' => 'account.deletion.withdrawn',
                'name' => 'Kontolöschung widerrufen',
                'draft_subject' => 'Kontolöschung wurde widerrufen',
                'draft_html' => <<<'HTML'
<p>Die Kontolöschung wurde erfolgreich widerrufen.</p>
<p>
    Für den weiteren Zugriff melden Sie sich bitte erneut an:
</p>
<p>
    <a href="{{ login_url }}">
        Zur Anmeldung
    </a>
</p>
HTML,
                'placeholders' => [
                    [
                        'key' => 'login_url',
                        'label' => 'Anmeldelink',
                        'description' => 'Link zur normalen Anmeldung nach dem Widerruf.',
                        'example_value' => 'https://my.vdb.test/anmelden',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'first_name',
                        'label' => 'Vorname',
                        'description' => 'Vorname der betroffenen Person.',
                        'example_value' => 'Erika',
                        'is_required' => false,
                    ],
                ],
            ],
            [
                'key' => 'account.deletion.stopped',
                'name' => 'Kontolöschung gestoppt',
                'draft_subject' => 'Kontolöschung wurde gestoppt',
                'draft_html' => <<<'HTML'
<p>Die Kontolöschung wurde administrativ gestoppt.</p>
<p>
    Bei Rückfragen wenden Sie sich bitte an {{ support_email }}.
</p>
HTML,
                'placeholders' => [
                    [
                        'key' => 'support_email',
                        'label' => 'Support-E-Mail',
                        'description' => 'Kontaktadresse für Rückfragen zu einer gestoppten Kontolöschung.',
                        'example_value' => 'support@example.test',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'first_name',
                        'label' => 'Vorname',
                        'description' => 'Vorname der betroffenen Person.',
                        'example_value' => 'Erika',
                        'is_required' => false,
                    ],
                ],
            ],
        ];

        foreach ($templates as $definition) {
            $template = EmailTemplate::query()->firstOrCreate(
                [
                    'key' => $definition['key'],
                ],
                [
                    'name' => $definition['name'],
                    'is_active' => false,
                    'draft_subject' => $definition['draft_subject'],
                    'draft_html' => $definition['draft_html'],
                    'updated_by_user_id' => null,
                ],
            );

            foreach ($definition['placeholders'] as $placeholder) {
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
}
