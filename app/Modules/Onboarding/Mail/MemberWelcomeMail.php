<?php

namespace App\Modules\Onboarding\Mail;

use App\Modules\Onboarding\Models\OnboardingDocument;
use App\Modules\Onboarding\Services\OnboardingDocuments;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Willkommensmail an neue Mitglieder, mit Satzung und Willkommensbrief im Anhang (soweit hochgeladen). */
final class MemberWelcomeMail extends Mailable
{
    /** @param  list<OnboardingDocument>  $documents */
    public function __construct(
        public readonly string $name,
        public readonly ?string $startsOn,
        public readonly ?string $portalUrl,
        public readonly array $documents,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Herzlich willkommen im VDBS');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.onboarding.member-welcome');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return array_map(
            fn (OnboardingDocument $document): Attachment => Attachment::fromStorageDisk(OnboardingDocuments::DISK, $document->path)->as($document->kind->label().'.pdf')->withMime('application/pdf'),
            $this->documents,
        );
    }
}
