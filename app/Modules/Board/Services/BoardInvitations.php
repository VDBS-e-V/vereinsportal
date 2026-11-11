<?php

namespace App\Modules\Board\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Board\Mail\BoardMeetingInvitationMail;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Support\IcsFeed;
use App\Support\Html\RichText;
use DomainException;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Einladungen zu Vorstandssitzungen per E-Mail. Abschaltbar (BoardCalendar::invitationsEnabled, standardmäßig aus); Empfänger sind aktive
 * Konten mit Rolle Vorstand, die die Benachrichtigung „board_meetings“ nicht abgestellt haben. Abgesagte Sitzungen werden nicht eingeladen.
 */
final class BoardInvitations
{
    public function __construct(private readonly BoardCalendar $calendar) {}

    /**
     * @return array{sent: int, failed: int}
     *
     * @throws DomainException
     */
    public function send(BoardMeeting $meeting, User $by, ?string $note = null): array
    {
        if (! $this->calendar->invitationsEnabled()) {
            throw new DomainException('Einladungen per E-Mail sind ausgeschaltet. Sie können sie auf der Seite „Sitzungen“ einschalten.');
        }

        if ($meeting->status === 'cancelled') {
            throw new DomainException('Zu einer abgesagten Sitzung wird nicht eingeladen.');
        }

        $recipients = $this->recipients();

        if ($recipients === []) {
            throw new DomainException('Es gibt keine Vorstandsmitglieder, die Einladungen erhalten.');
        }

        $agenda = RichText::isEmpty($meeting->agenda) ? '' : RichText::toPlain((string) $meeting->agenda);
        $ics = IcsFeed::build(collect([$meeting]), parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'portal', 'Vorstandssitzung');
        $url = route('board.meetings.show', $meeting);
        $sent = $failed = 0;

        foreach ($recipients as $user) {
            try {
                Mail::to($user->email)->send(new BoardMeetingInvitationMail($meeting, $url, $agenda, trim((string) $note), $ics));
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        if ($sent > 0) {
            $meeting->forceFill(['invited_at' => now()])->saveQuietly();
            AuditLog::record(AuditEvent::BoardInvitationSent, $by, null, ['sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y'), 'empfaenger' => $sent]);
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /** @return list<User> */
    public function recipients(): array
    {
        $board = User::query()
            ->where('status', UserStatus::Active->value)
            ->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', RoleKey::BoardMember->value)))
            ->get();

        return app(NotificationPreferences::class)->filter($board, 'board_meetings');
    }
}
