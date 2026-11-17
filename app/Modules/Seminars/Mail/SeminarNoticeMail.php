<?php

namespace App\Modules\Seminars\Mail;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Mitteilungen im Seminarablauf an Schule, Koordination und Team (Art in $kind, optionaler Text der Absender-Person in $extra). */
final class SeminarNoticeMail extends Mailable
{
    /** @var array<string, string> Art => Betreff (ohne Seminartitel) */
    public const SUBJECTS = [
        'school_received' => 'Ihre Seminaranfrage ist eingegangen',
        'school_question' => 'Rückfrage zu Ihrer Seminaranfrage',
        'school_rejected' => 'Ihre Seminaranfrage',
        'school_quote' => 'Kostenvoranschlag zu Ihrem Seminar',
        'school_briefing' => 'Ablauf Ihres Seminars',
        'school_cancelled' => 'Ihr Seminar wurde abgesagt',
        'school_rescheduled' => 'Neue Termine für Ihr Seminar',
        'released' => 'Seminar freigegeben',
        'tender' => 'Neues Seminar zur Mitarbeit',
        'lead' => 'Sie leiten das Seminar',
        'onboarding' => 'Einladung zum Onboarding',
        'team_rescheduled' => 'Neue Termine für das Seminar',
    ];

    public function __construct(public readonly string $kind, public readonly Seminar $seminar, public readonly string $url, public readonly ?string $extra = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: (self::SUBJECTS[$this->kind] ?? 'Mitteilung zum Seminar').': '.$this->seminar->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.seminars.notice');
    }
}
