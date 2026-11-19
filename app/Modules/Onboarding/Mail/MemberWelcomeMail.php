<?php

namespace App\Modules\Onboarding\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;
use App\Modules\Onboarding\Models\OnboardingDocument;
use App\Modules\Onboarding\Services\OnboardingDocuments;
use Illuminate\Mail\Mailables\Attachment;

/** Willkommensmail an neue Mitglieder, mit Satzung und Willkommensbrief im Anhang (soweit hochgeladen). */
final class MemberWelcomeMail extends TemplatedMail
{
    /** @param  list<OnboardingDocument>  $documents */
    public function __construct(
        public readonly string $name,
        public readonly ?string $startsOn,
        public readonly ?string $portalUrl,
        public readonly array $documents,
    ) {}

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return array_map(
            fn (OnboardingDocument $document): Attachment => Attachment::fromStorageDisk(OnboardingDocuments::DISK, $document->path)->as($document->kind->label().'.pdf')->withMime('application/pdf'),
            $this->documents,
        );
    }

    protected function systemKey(): string
    {
        return 'onboarding.member_welcome';
    }

    protected function values(): array
    {
        return ['anrede' => self::greeting($this->name), 'beginn' => (string) $this->startsOn, 'dokumente' => array_map(fn (OnboardingDocument $document): string => $document->kind->label(), $this->documents), 'portal_url' => (string) $this->portalUrl];
    }
}
