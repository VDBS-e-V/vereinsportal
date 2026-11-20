<?php

namespace App\Modules\Contacts\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;
use Illuminate\Mail\Mailables\Headers;

/** Der persönliche Zugang (Link und Code) für eine Adresse der Mailingliste. */
final class ContactAccessMail extends TemplatedMail
{
    public function __construct(public readonly string $greetingName, public readonly string $accessLink, public readonly string $accessCode, public readonly string $codeHint, public readonly string $unsubscribeUrl) {}

    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click']);
    }

    protected function systemKey(): string
    {
        return 'contacts.access';
    }

    protected function values(): array
    {
        return ['anrede' => self::greeting($this->greetingName), 'link' => $this->accessLink, 'code' => $this->accessCode, 'code_hinweis' => $this->codeHint, 'code_url' => route('access.code'), 'abmelden_url' => $this->unsubscribeUrl];
    }
}
