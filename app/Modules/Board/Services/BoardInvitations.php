<?php

namespace App\Modules\Board\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Board\Mail\BoardGuestInvitationMail;
use App\Modules\Board\Mail\BoardMeetingInvitationMail;
use App\Modules\Board\Models\BoardMeetingGuest;
use App\Modules\Contracts\Services\ContractSettings;
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

    /**
     * Lädt externe Gäste ein, mit Kalenderdatei. Ohne Auswahl bekommen alle Gäste der Sitzung die Einladung.
     *
     * @param  list<int>|null  $guestIds
     * @return array{sent: int, failed: int}
     *
     * @throws DomainException
     */
    public function sendToGuests(BoardMeeting $meeting, User $by, ?array $guestIds = null, ?string $note = null, bool $withAgenda = false): array
    {
        if (! $this->calendar->invitationsEnabled()) {
            throw new DomainException('Einladungen per E-Mail sind ausgeschaltet. Sie können sie auf der Seite „Sitzungen“ einschalten.');
        }

        if ($meeting->status === 'cancelled') {
            throw new DomainException('Zu einer abgesagten Sitzung wird nicht eingeladen.');
        }

        $guests = $meeting->guests()->when($guestIds !== null && $guestIds !== [], fn ($query) => $query->whereIn('id', $guestIds))->get();

        if ($guests->isEmpty()) {
            throw new DomainException('Es gibt keine Gäste, die eingeladen werden könnten.');
        }

        $agenda = $withAgenda && ! RichText::isEmpty($meeting->agenda) ? RichText::toPlain((string) $meeting->agenda) : '';
        $ics = IcsFeed::build(collect([$meeting]), parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'portal', 'Sitzung');
        $club = app(ContractSettings::class)->get('club_name');
        $sent = $failed = 0;

        foreach ($guests as $guest) {
            try {
                Mail::to($guest->email)->send(new BoardGuestInvitationMail($meeting, $guest, $club, $agenda, trim((string) $note), $ics, route('pages.show', 'datenschutz'), max(1, (int) config('portal.board.guest_retention_days', 30))));
                $guest->forceFill(['invited_at' => now()])->save();
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        if ($sent > 0) {
            AuditLog::record(AuditEvent::BoardInvitationSent, $by, null, ['sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y'), 'gaeste' => $sent]);
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
