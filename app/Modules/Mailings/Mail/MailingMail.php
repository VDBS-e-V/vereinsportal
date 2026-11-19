<?php

namespace App\Modules\Mailings\Mail;

use App\Modules\MailBuilder\Services\MailComposer;
use App\Modules\MailBuilder\Support\RenderedMail;
use App\Modules\Mailings\Models\MailingAttachment;
use App\Support\Html\RichText;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/** Ein Schreiben des Vorstands oder der Koordination. Der Text ist HTML aus dem Editor und wird beim Ausgeben bereinigt (rich_text). */
final class MailingMail extends Mailable
{
    public function __construct(public readonly string $subjectLine, public readonly string $bodyHtml, public readonly string $kind, public readonly ?string $unsubscribeUrl = null, public readonly bool $mandatory = false, public readonly ?string $fromName = null, public readonly ?string $replyToAddress = null, public readonly array $attachmentFiles = []) {}

    public function envelope(): Envelope
    {
        $from = filled($this->fromName) && filled(config('mail.from.address')) ? new Address((string) config('mail.from.address'), (string) $this->fromName) : null;

        return new Envelope(from: $from, replyTo: filled($this->replyToAddress) ? [new Address((string) $this->replyToAddress)] : [], subject: $this->subjectLine);
    }

    /** Abbestellbare Mails tragen List-Unsubscribe und One-Click (RFC 2369 und RFC 8058). */
    public function headers(): Headers
    {
        return new Headers(text: $this->unsubscribeUrl !== null ? ['List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click'] : []);
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return array_values(array_map(fn (array $file): Attachment => Attachment::fromStorageDisk(MailingAttachment::DISK, $file['path'])->as($file['name'])->withMime($file['mime']), $this->attachmentFiles));
    }

    public function content(): Content
    {
        $rendered = $this->rendered();

        return new Content(htmlString: $rendered->html, text: 'mail.plain', with: ['text' => $rendered->text]);
    }

    /** Der Text des Schreibens im zentralen Mail-Design, mit Hinweis zum Empfängerkreis und Abmeldelink in der Fußzeile. */
    private function rendered(): RenderedMail
    {
        $club = 'Verband für Demokratiebildung und Bibliotheken an Schulen e. V.';
        $notice = match (true) {
            $this->mandatory => 'Diese Nachricht ist eine Pflichtmitteilung des '.$club.' an seine Mitglieder und kann nicht abbestellt werden.',
            $this->kind === 'members' => 'Sie erhalten diese Nachricht als Mitglied des '.$club,
            $this->kind === 'team' => 'Sie erhalten diese Nachricht als Teamende:r des '.$club,
            $this->kind === 'schools' => 'Sie erhalten diese Nachricht als Schulkonto im Portal des '.$club,
            default => 'Diese Nachricht kommt vom '.$club,
        };
        $style = 'margin:0 0 6px;font-size:13px;line-height:1.5;color:#475569;';
        $footer = '<p style="'.$style.'" class="vdbs-muted">'.e($notice).'</p>';

        if ($this->unsubscribeUrl) {
            $footer .= '<p style="'.$style.'" class="vdbs-muted">Sie möchten solche Mitteilungen nicht mehr bekommen? <a href="'.e($this->unsubscribeUrl).'" class="vdbs-link">Mitteilungen dieser Art abbestellen</a></p>';
        }

        $doc = ['version' => 1, 'preheader' => '', 'blocks' => [['id' => 'body', 'type' => 'text', 'props' => ['html' => RichText::render($this->bodyHtml)]]]];

        return app(MailComposer::class)->render($doc, [], ['subject' => $this->subjectLine, 'footer_extra' => $footer]);
    }
}
