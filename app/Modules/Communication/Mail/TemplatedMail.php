<?php

namespace App\Modules\Communication\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class TemplatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $subjectLine,
        public readonly string $htmlContent,
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine)
            ->html($this->htmlContent);
    }
}
