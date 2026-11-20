<?php

namespace App\Modules\MailBuilder\Mail;

use App\Modules\MailBuilder\Services\SystemMailRenderer;
use App\Modules\MailBuilder\Support\RenderedMail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Grundlage aller Systemmails mit festem Text: Betreff und Inhalt kommen aus dem Mail-Builder (oder dem Standardtext im Register),
 * das Mailable liefert nur den Schlüssel und die Werte der Platzhalter. Anhänge und Kopfzeilen bleiben Sache der einzelnen Klasse.
 */
abstract class TemplatedMail extends Mailable
{
    private ?RenderedMail $rendered = null;

    /** Schlüssel der Systemmail im Register (SystemMails). */
    abstract protected function systemKey(): string;

    /**
     * Werte der Platzhalter dieser Mail.
     *
     * @return array<string, string|list<string>>
     */
    abstract protected function values(): array;

    protected function rendered(): RenderedMail
    {
        return $this->rendered ??= app(SystemMailRenderer::class)->render($this->systemKey(), $this->values());
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->rendered()->subject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->rendered()->html, text: 'mail.plain', with: ['text' => $this->rendered()->text]);
    }

    /** Anrede für die Mail: „Guten Tag“ mit Namen, wenn einer bekannt ist. */
    protected static function greeting(?string $name): string
    {
        $name = trim((string) $name);

        return $name === '' ? 'Guten Tag' : 'Guten Tag '.$name;
    }
}
