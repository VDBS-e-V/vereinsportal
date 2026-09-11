<?php

namespace Database\Seeders;

use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use Illuminate\Database\Seeder;

final class PortalInvitationEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = EmailTemplate::query()->firstOrCreate(
            ['key' => 'auth.portal-invitation'],
            [
                'name' => 'Portalzugang einladen',
                'is_active' => false,
                'draft_subject' => 'Einladung zum Vereinsportal',
                'draft_html' => <<<'HTML'
<p>Hallo {{ first_name }},</p>
<p>für Sie wurde ein Zugang zum Vereinsportal vorbereitet.</p>
<p><a href="{{ invitation_url }}">Portalzugang einrichten</a></p>
<p>Der Link ist bis {{ expires_at }} gültig und kann nur einmal verwendet werden.</p>
HTML,
                'updated_by_user_id' => null,
            ],
        );

        $placeholders = [
            [
                'key' => 'first_name',
                'label' => 'Vorname',
                'description' => 'Vorname der eingeladenen Person.',
                'example_value' => 'Erika',
                'is_required' => true,
            ],
            [
                'key' => 'invitation_url',
                'label' => 'Einladungslink',
                'description' => 'Signierter Einmal-Link zur Einrichtung des Portalzugangs.',
                'example_value' => 'https://my.vdb.test/einladung/example/1/token',
                'is_required' => true,
            ],
            [
                'key' => 'expires_at',
                'label' => 'Ablaufzeitpunkt',
                'description' => 'Zeitpunkt, bis zu dem die Einladung gültig ist.',
                'example_value' => '14.09.2026 12:00 UTC',
                'is_required' => true,
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
