<?php

namespace App\Modules\MailBuilder\Services;

use App\Modules\MailBuilder\Models\MailLayout;
use App\Modules\MailBuilder\Models\MailTemplate;
use App\Modules\MailBuilder\Support\MailCheck;
use App\Modules\MailBuilder\Support\RenderedMail;
use App\Modules\MailBuilder\Support\SystemMails;
use Throwable;

/**
 * Rendert eine Systemmail: die gespeicherte Fassung aus dem Builder, wenn es eine gibt und sie in Ordnung ist, sonst den Standardtext aus dem Register.
 * Eine Mail geht also immer raus, auch wenn eine Änderung kaputt ist oder die Datenbank nichts liefert.
 */
final class SystemMailRenderer
{
    public const CLUB = 'Verband für Demokratiebildung und Bibliotheken an Schulen e. V.';

    public function __construct(private readonly MailComposer $composer) {}

    /**
     * @param  array<string, string|list<string>>  $values
     */
    public function render(string $key, array $values, bool $forceDark = false): RenderedMail
    {
        $definition = SystemMails::get($key);
        $values += [
            'anrede' => 'Guten Tag',
            'verein' => self::CLUB,
            'portal' => (string) config('app.url'),
            'jahr' => now()->format('Y'),
            'heute' => now()->format('d.m.Y'),
        ];
        $options = ['raw' => $definition->raw(), 'allowed' => $definition->allowed(), 'protected' => $definition->protected(), 'force_dark' => $forceDark];

        try {
            $template = MailTemplate::query()->where('system_key', $key)->where('status', 'published')->first();

            if ($template !== null && is_array($template->blocks)) {
                $layout = $template->layout_id !== null ? MailLayout::query()->find($template->layout_id) : null;
                $doc = $template->blocks;
                $doc['preheader'] = (string) ($template->preheader ?? ($doc['preheader'] ?? ''));
                $mail = $this->composer->render($doc, $values, $options + ['subject' => filled($template->subject) ? (string) $template->subject : $definition->subject], $layout);

                if (! MailCheck::hasErrors($mail->issues) && trim($mail->subject) !== '') {
                    return $mail;
                }
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $this->composer->render($definition->document(), $values, $options + ['subject' => $definition->subject]);
    }
}
