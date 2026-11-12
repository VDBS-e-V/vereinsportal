<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Board\Mail\BoardGuestInvitationMail;
use App\Modules\Board\Mail\BoardMeetingInvitationMail;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Models\BoardMeetingDocument;
use App\Modules\Board\Models\BoardMeetingGuest;
use App\Modules\Board\Models\BoardResolution;
use App\Modules\Board\Services\BoardCalendar;
use App\Modules\Board\Support\IcsFeed;
use App\Modules\People\Models\Person;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Services\TaskProjects;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function bmUser(RoleKey $role, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function bmData(array $override = []): array
{
    return array_merge(['title' => 'Vorstandssitzung Dezember', 'date' => now()->addDays(10)->format('Y-m-d'), 'start_time' => '19:00', 'end_time' => '21:00', 'location' => 'Geschäftsstelle', 'status' => 'planned'], $override);
}

it('creates a meeting with agenda, attendance and minutes and shows it', function () {
    $board = bmUser(RoleKey::BoardMember, ['first_name' => 'Berta', 'last_name' => 'Vorstand']);
    $other = bmUser(RoleKey::BoardMember, ['first_name' => 'Carl', 'last_name' => 'Beisitz']);
    $this->actingAs($board)->get(route('board.meetings.create'))->assertOk();

    $this->post(route('board.meetings.store'), bmData(['agenda' => '<p>1. Finanzen</p>', 'minutes' => '<p>Beschluss: Budget angenommen</p>', 'present' => [$board->id], 'excused' => [$other->id], 'online_link' => 'https://meet.example.org/vorstand']))->assertRedirect();

    $meeting = BoardMeeting::query()->firstOrFail();
    expect($meeting->starts_at->format('H:i'))->toBe('19:00')->and($meeting->attendees)->toHaveCount(2)
        ->and(AuditEntry::query()->where('event', 'board.meeting_saved')->count())->toBe(1);

    $this->get(route('board.meetings.show', $meeting))->assertOk()->assertSee('1. Finanzen')->assertSee('Beschluss: Budget angenommen')->assertSee('Berta Vorstand')->assertSee('Carl Beisitz')->assertSee('Entwurf');
    $this->get(route('board.meetings.index'))->assertOk()->assertSee('Vorstandssitzung Dezember')->assertSee('Anstehende Sitzungen');
});

it('validates the form', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));

    $this->post(route('board.meetings.store'), bmData(['title' => '']))->assertSessionHasErrors('title');
    $this->post(route('board.meetings.store'), bmData(['end_time' => '18:00']))->assertSessionHasErrors('end_time');
    $this->post(route('board.meetings.store'), bmData(['online_link' => 'http://meet.example.org']))->assertSessionHasErrors('online_link');
    $this->post(route('board.meetings.store'), bmData(['status' => 'x']))->assertSessionHasErrors('status');
    expect(BoardMeeting::query()->count())->toBe(0);
});

it('approves minutes and withdraws the approval when they change', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $meeting = BoardMeeting::query()->create(['title' => 'Sitzung', 'starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHours(2), 'status' => 'held']);

    $this->post(route('board.meetings.approve', $meeting))->assertSessionHas('error');
    expect($meeting->fresh()->minutes_approved_on)->toBeNull();

    $this->put(route('board.meetings.update', $meeting), bmData(['date' => now()->subDay()->format('Y-m-d'), 'status' => 'held', 'minutes' => '<p>Erste Fassung</p>']))->assertRedirect();
    $this->post(route('board.meetings.approve', $meeting))->assertSessionHas('success');
    expect($meeting->fresh()->minutes_approved_on)->not->toBeNull()
        ->and(AuditEntry::query()->where('event', 'board.minutes_approved')->count())->toBe(1);

    $this->put(route('board.meetings.update', $meeting), bmData(['date' => now()->subDay()->format('Y-m-d'), 'status' => 'held', 'minutes' => '<p>Geänderte Fassung</p>']))->assertRedirect();
    expect($meeting->fresh()->minutes_approved_on)->toBeNull();
});

it('deletes single and several meetings', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $a = BoardMeeting::query()->create(['title' => 'A', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
    $b = BoardMeeting::query()->create(['title' => 'B', 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour()]);
    $c = BoardMeeting::query()->create(['title' => 'C', 'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHour()]);

    $this->delete(route('board.meetings.destroy', $a))->assertRedirect(route('board.meetings.index'));
    $this->post(route('board.meetings.destroy-many'), ['ids' => [$b->id, $c->id]])->assertRedirect();
    $this->post(route('board.meetings.destroy-many'), [])->assertSessionHasErrors('ids');

    expect(BoardMeeting::query()->count())->toBe(0)->and(AuditEntry::query()->where('event', 'board.meeting_deleted')->count())->toBe(3);
});

it('serves the calendar feed only with the secret token', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $this->post(route('board.meetings.store'), bmData(['location' => 'Büro, 1. Stock; Raum 3', 'online_link' => 'https://meet.example.org/v', 'agenda' => '<p>Geheime Tagesordnung</p>', 'minutes' => '<p>Geheimes Protokoll</p>']))->assertRedirect();
    $this->post(route('board.meetings.store'), bmData(['title' => 'Abgesagt', 'status' => 'cancelled']))->assertRedirect();

    $url = app(BoardCalendar::class)->feedUrl();
    auth()->logout();

    $response = $this->get($url)->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/calendar');
    $ics = $response->getContent();

    expect($ics)->toContain('BEGIN:VCALENDAR')->toContain('SUMMARY:Vorstandssitzung Dezember')->toContain('T190000')->toContain('TZID=Europe/Berlin')
        ->toContain('LOCATION:Büro\, 1. Stock\; Raum 3')->toContain('STATUS:CANCELLED')->toContain("\r\n")
        ->not->toContain('Geheime')->not->toContain('Tagesordnung');

    $this->get(route('board.feed', ['token' => str_repeat('a', 40)]))->assertNotFound();
});

it('renews the feed address so that the old one stops working', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $old = app(BoardCalendar::class)->feedUrl();
    $this->get($old)->assertOk();

    $this->post(route('board.meetings.feed.renew'))->assertRedirect(route('board.meetings.index'));

    $this->get($old)->assertNotFound();
    $this->get(app(BoardCalendar::class)->feedUrl())->assertOk();
    expect(AuditEntry::query()->where('event', 'board.calendar_changed')->count())->toBe(1);
});

it('folds and escapes ics lines', function () {
    expect(IcsFeed::escape("a,b;c\\d\ne"))->toBe('a\,b\;c\\\\d\ne');

    $folded = IcsFeed::fold('SUMMARY:'.str_repeat('ä', 80));
    foreach (explode("\r\n", $folded) as $line) {
        expect(strlen($line))->toBeLessThanOrEqual(75);
    }
    expect(str_replace("\r\n ", '', $folded))->toBe('SUMMARY:'.str_repeat('ä', 80));
});

it('saves the optional external calendar link and accepts only https', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));

    foreach (['javascript:alert(1)', 'http://example.org/k', 'kein link'] as $bad) {
        $this->put(route('board.meetings.calendar'), ['calendar_url' => $bad])->assertSessionHasErrors('calendar_url');
    }

    $this->put(route('board.meetings.calendar'), ['calendar_url' => 'https://cloud.example.org/cal/vorstand'])->assertSessionHasNoErrors();
    $this->get(route('board.meetings.index'))->assertOk()->assertSee('https://cloud.example.org/cal/vorstand', false);
});

it('keeps the meetings from everyone but the board', function () {
    $meeting = BoardMeeting::query()->create(['title' => 'Intern', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
    $this->get(route('board.meetings.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::AdministrationStaff, RoleKey::Coordination, RoleKey::Team, RoleKey::Member, RoleKey::Administration] as $role) {
        $this->actingAs(bmUser($role));
        $this->get(route('board.meetings.index'))->assertForbidden();
        $this->get(route('board.meetings.show', $meeting))->assertForbidden();
        $this->post(route('board.meetings.store'), bmData())->assertForbidden();
        $this->delete(route('board.meetings.destroy', $meeting))->assertForbidden();
    }

    expect(BoardMeeting::query()->count())->toBe(1);
});

it('lists the meetings in the board navigation', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember))->get(route('board.overview'))->assertOk()->assertSee(route('board.meetings.index'), false);
});

function bmFile(string $name, string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'bm');
    file_put_contents($path, $content);

    return new UploadedFile($path, $name, null, null, true);
}

function bmMeeting(array $override = []): BoardMeeting
{
    return BoardMeeting::query()->create($override + ['title' => 'Sitzung', 'starts_at' => now()->addDays(5)->setTime(19, 0), 'ends_at' => now()->addDays(5)->setTime(21, 0)]);
}

const BM_PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

it('stores, downloads and deletes attachments of a meeting', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $meeting = bmMeeting();

    $this->post(route('board.meetings.documents.store', $meeting), ['title' => 'Protokoll Oktober', 'files' => [bmFile('protokoll.pdf', BM_PDF), bmFile('anlage.txt', 'Zahlen')]])->assertRedirect()->assertSessionHas('success');

    $documents = $meeting->documents()->get();
    expect($documents)->toHaveCount(2)->and(AuditEntry::query()->where('event', 'board.document_added')->count())->toBe(2);
    Storage::disk('local')->assertExists($documents[0]->path);

    $this->get(route('board.meetings.show', $meeting))->assertOk()->assertSee('Protokoll Oktober');
    $this->get(route('board.meetings.documents.download', [$meeting, $documents[0]]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

    $other = bmMeeting(['title' => 'Andere']);
    $this->get(route('board.meetings.documents.download', [$other, $documents[0]]))->assertNotFound();

    $this->post(route('board.meetings.documents.destroy', $meeting), ['ids' => [$documents[0]->id, $documents[1]->id]])->assertRedirect();
    expect(BoardMeetingDocument::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($documents[0]->path);
});

it('rejects images and files that do not match their extension', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $meeting = bmMeeting();
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

    $this->post(route('board.meetings.documents.store', $meeting), ['files' => [bmFile('foto.png', $png)]])->assertSessionHasErrors('documents');
    $this->post(route('board.meetings.documents.store', $meeting), ['files' => [bmFile('falsch.pdf', 'kein pdf')]])->assertSessionHasErrors('documents');
    $this->post(route('board.meetings.documents.store', $meeting), ['files' => [bmFile('skript.php', '<?php echo 1;')]])->assertSessionHasErrors('documents');
    $this->post(route('board.meetings.documents.store', $meeting), [])->assertSessionHasErrors('files');

    expect(BoardMeetingDocument::query()->count())->toBe(0);
});

it('removes the files when a meeting is deleted', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $meeting = bmMeeting();
    $this->post(route('board.meetings.documents.store', $meeting), ['files' => [bmFile('protokoll.pdf', BM_PDF)]]);
    $path = $meeting->documents()->firstOrFail()->path;

    $this->delete(route('board.meetings.destroy', $meeting))->assertRedirect();

    Storage::disk('local')->assertMissing($path);
    expect(BoardMeetingDocument::query()->count())->toBe(0);
});

it('keeps the attachments from everyone but the board', function () {
    $meeting = bmMeeting();
    $document = $meeting->documents()->create(['title' => 'X', 'original_name' => 'x.pdf', 'path' => 'board/1/x.pdf', 'mime' => 'application/pdf', 'size' => 10]);

    foreach ([RoleKey::AdministrationStaff, RoleKey::Coordination, RoleKey::Team, RoleKey::Member] as $role) {
        $this->actingAs(bmUser($role));
        $this->get(route('board.meetings.documents.download', [$meeting, $document]))->assertForbidden();
        $this->post(route('board.meetings.documents.store', $meeting), ['files' => [bmFile('a.txt', 'a')]])->assertForbidden();
        $this->post(route('board.meetings.invite', $meeting))->assertForbidden();
        $this->put(route('board.meetings.invitations.toggle'), ['enabled' => 1])->assertForbidden();
    }
});

it('keeps invitations switched off until the board enables them', function () {
    Mail::fake();
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $meeting = bmMeeting();

    expect(app(BoardCalendar::class)->invitationsEnabled())->toBeFalse();
    $this->get(route('board.meetings.show', $meeting))->assertOk()->assertDontSee('Einladung senden');
    $this->post(route('board.meetings.invite', $meeting))->assertSessionHas('error');
    $this->post(route('board.meetings.store'), bmData(['send_invitation' => '1']))->assertRedirect();

    Mail::assertNothingSent();
});

it('invites the board with a calendar file and respects switches and preferences', function () {
    Mail::fake();
    $a = bmUser(RoleKey::BoardMember);
    $b = bmUser(RoleKey::BoardMember);
    $off = bmUser(RoleKey::BoardMember);
    $notBoard = bmUser(RoleKey::Coordination);
    DB::table('notification_preferences')->insert(['user_id' => $off->id, 'key' => 'board_meetings', 'enabled' => false, 'created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($a)->put(route('board.meetings.invitations.toggle'), ['enabled' => 1])->assertRedirect();
    expect(AuditEntry::query()->where('event', 'board.calendar_changed')->count())->toBe(1);

    $meeting = bmMeeting(['title' => 'Jahresplanung', 'agenda' => '<p>TOP 1 Haushalt</p>', 'location' => 'Büro']);
    $this->get(route('board.meetings.show', $meeting))->assertSee('Einladung senden');
    $this->post(route('board.meetings.invite', $meeting), ['note' => 'Bitte pünktlich.'])->assertSessionHas('success');

    Mail::assertSent(BoardMeetingInvitationMail::class, 2);
    Mail::assertSent(BoardMeetingInvitationMail::class, fn ($mail) => $mail->hasTo($a->email) && str_contains($mail->ics, 'BEGIN:VEVENT') && $mail->note === 'Bitte pünktlich.' && str_contains($mail->agenda, 'TOP 1 Haushalt') && $mail->envelope()->subject === 'Einladung: Jahresplanung am '.$meeting->starts_at->format('d.m.Y'));
    Mail::assertNotSent(BoardMeetingInvitationMail::class, fn ($mail) => $mail->hasTo($off->email) || $mail->hasTo($notBoard->email));
    expect($meeting->fresh()->invited_at)->not->toBeNull()->and(AuditEntry::query()->where('event', 'board.invitation_sent')->count())->toBe(1);

    $this->get(route('board.meetings.show', $meeting))->assertSee('Zuletzt eingeladen');
});

it('invites on saving when asked and refuses cancelled meetings', function () {
    Mail::fake();
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $this->put(route('board.meetings.invitations.toggle'), ['enabled' => 1]);

    $this->post(route('board.meetings.store'), bmData(['send_invitation' => '1']))->assertRedirect();
    Mail::assertSent(BoardMeetingInvitationMail::class, 1);

    $cancelled = bmMeeting(['status' => 'cancelled']);
    $this->post(route('board.meetings.invite', $cancelled))->assertSessionHas('error');
    Mail::assertSent(BoardMeetingInvitationMail::class, 1);

    $this->put(route('board.meetings.invitations.toggle'), [])->assertRedirect();
    expect(app(BoardCalendar::class)->invitationsEnabled())->toBeFalse();
});

it('adds, lists and removes external guests', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $meeting = bmMeeting();

    $this->post(route('board.meetings.guests.store', $meeting), ['name' => 'Gerda Gast', 'email' => '  Gerda@Beispiel.Example ', 'organization' => 'Beratung eG'])->assertRedirect()->assertSessionHas('success');
    $this->post(route('board.meetings.guests.store', $meeting), ['name' => 'Doppelt', 'email' => 'gerda@beispiel.example'])->assertSessionHasErrors('email');
    $this->post(route('board.meetings.guests.store', $meeting), ['name' => '', 'email' => 'kaputt'])->assertSessionHasErrors(['name', 'email']);

    $guest = BoardMeetingGuest::query()->firstOrFail();
    expect($guest->email)->toBe('gerda@beispiel.example')->and(BoardMeetingGuest::query()->count())->toBe(1);
    $this->get(route('board.meetings.show', $meeting))->assertOk()->assertSee('Gerda Gast (Beratung eG)')->assertSee('gerda@beispiel.example');

    $this->post(route('board.meetings.guests.store', bmMeeting(['title' => 'Andere'])), ['name' => 'Gerda Gast', 'email' => 'gerda@beispiel.example'])->assertSessionHasNoErrors();

    $this->post(route('board.meetings.guests.destroy', $meeting), ['ids' => [$guest->id]])->assertRedirect();
    expect($meeting->guests()->count())->toBe(0)->and(AuditEntry::query()->where('event', 'board.guests_changed')->count())->toBe(3);
});

it('invites guests with a calendar file only when invitations are on', function () {
    Mail::fake();
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $meeting = bmMeeting(['title' => 'Strategietag', 'agenda' => '<p>TOP intern</p>', 'location' => 'Büro']);
    $one = $meeting->guests()->create(['name' => 'Gerda Gast', 'email' => 'gerda@beispiel.example']);
    $two = $meeting->guests()->create(['name' => 'Hans Gast', 'email' => 'hans@beispiel.example']);

    $this->post(route('board.meetings.guests.invite', $meeting))->assertSessionHas('error');
    Mail::assertNothingSent();

    $this->put(route('board.meetings.invitations.toggle'), ['enabled' => 1]);

    $this->post(route('board.meetings.guests.invite', $meeting), ['ids' => [$one->id], 'note' => 'Bitte Unterlagen mitbringen.'])->assertSessionHas('success');
    Mail::assertSent(BoardGuestInvitationMail::class, 1);
    Mail::assertSent(BoardGuestInvitationMail::class, fn ($mail) => $mail->hasTo('gerda@beispiel.example') && str_contains($mail->ics, 'BEGIN:VEVENT') && $mail->agenda === '' && $mail->note === 'Bitte Unterlagen mitbringen.' && ! str_contains($mail->render(), '/vorstand/'));
    expect($one->fresh()->invited_at)->not->toBeNull()->and($two->fresh()->invited_at)->toBeNull();

    $this->post(route('board.meetings.guests.invite', $meeting), ['with_agenda' => '1'])->assertSessionHas('success');
    Mail::assertSent(BoardGuestInvitationMail::class, 3);
    Mail::assertSent(BoardGuestInvitationMail::class, fn ($mail) => $mail->hasTo('hans@beispiel.example') && str_contains($mail->agenda, 'TOP intern'));
    Mail::assertNotSent(BoardMeetingInvitationMail::class);
    expect(AuditEntry::query()->where('event', 'board.invitation_sent')->count())->toBe(2);
});

it('does not invite guests to a cancelled meeting or without guests', function () {
    Mail::fake();
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $this->put(route('board.meetings.invitations.toggle'), ['enabled' => 1]);

    $empty = bmMeeting();
    $this->post(route('board.meetings.guests.invite', $empty))->assertSessionHas('error');

    $cancelled = bmMeeting(['status' => 'cancelled']);
    $cancelled->guests()->create(['name' => 'Gerda Gast', 'email' => 'gerda@beispiel.example']);
    $this->post(route('board.meetings.guests.invite', $cancelled))->assertSessionHas('error');

    Mail::assertNothingSent();
});

it('keeps the guest list from everyone but the board and removes guests with the meeting', function () {
    $meeting = bmMeeting();
    $meeting->guests()->create(['name' => 'Gerda Gast', 'email' => 'gerda@beispiel.example']);

    foreach ([RoleKey::AdministrationStaff, RoleKey::Coordination, RoleKey::Member] as $role) {
        $this->actingAs(bmUser($role));
        $this->post(route('board.meetings.guests.store', $meeting), ['name' => 'X', 'email' => 'x@beispiel.example'])->assertForbidden();
        $this->post(route('board.meetings.guests.invite', $meeting))->assertForbidden();
        $this->post(route('board.meetings.guests.destroy', $meeting), ['ids' => [1]])->assertForbidden();
    }

    $this->actingAs(bmUser(RoleKey::BoardMember))->delete(route('board.meetings.destroy', $meeting));
    expect(BoardMeetingGuest::query()->count())->toBe(0);
});

it('deletes guest data after the retention period but keeps the meeting', function () {
    config(['portal.board.guest_retention_days' => 30]);
    $old = bmMeeting(['title' => 'Alt', 'starts_at' => now()->subDays(40), 'ends_at' => now()->subDays(40)->addHours(2)]);
    $recent = bmMeeting(['title' => 'Neulich', 'starts_at' => now()->subDays(10), 'ends_at' => now()->subDays(10)->addHours(2)]);
    $future = bmMeeting(['title' => 'Kommend']);

    foreach ([$old, $recent, $future] as $i => $meeting) {
        $meeting->guests()->create(['name' => 'Gast '.$i, 'email' => 'gast'.$i.'@beispiel.example']);
    }

    $this->artisan('board:prune-guests', ['--dry-run' => true])->expectsOutputToContain('1 Gast')->assertSuccessful();
    expect(BoardMeetingGuest::query()->count())->toBe(3);

    $this->artisan('board:prune-guests')->assertSuccessful();

    expect(BoardMeetingGuest::query()->pluck('email')->all())->toEqualCanonicalizing(['gast1@beispiel.example', 'gast2@beispiel.example'])
        ->and(BoardMeeting::query()->count())->toBe(3);
});

it('tells guests about the processing of their data in the invitation', function () {
    Mail::fake();
    config(['portal.board.guest_retention_days' => 45]);
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $this->put(route('board.meetings.invitations.toggle'), ['enabled' => 1]);
    $meeting = bmMeeting();
    $meeting->guests()->create(['name' => 'Gerda Gast', 'email' => 'gerda@beispiel.example', 'organization' => 'Beratung eG']);

    $this->post(route('board.meetings.guests.invite', $meeting))->assertSessionHas('success');

    Mail::assertSent(BoardGuestInvitationMail::class, function ($mail) {
        $html = $mail->render();

        return str_contains($html, 'Hinweis zum Datenschutz') && str_contains($html, '45 Tage nach der Sitzung') && str_contains($html, route('pages.show', 'datenschutz')) && str_contains($html, 'sowie Ihre Organisation')
            && ! str_contains($html, '/vorstand/sitzungen') && $mail->privacyUrl !== '';
    });
});

it('mentions board meetings and guests in the default privacy statement and the guest list', function () {
    $this->get(route('pages.show', 'datenschutz'))->assertOk()->assertSee('Vorstandssitzungen und Gäste')->assertSee('30 Tage nach der Sitzung');

    $this->actingAs(bmUser(RoleKey::BoardMember));
    $this->get(route('board.meetings.show', bmMeeting()))->assertOk()->assertSee('30 Tage nach der Sitzung automatisch gelöscht');
});

it('records resolutions with a yearly running number and lists them', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $meeting = bmMeeting(['title' => 'Haushalt', 'starts_at' => '2027-03-10 19:00:00', 'ends_at' => '2027-03-10 21:00:00']);
    $next = bmMeeting(['title' => 'Später', 'starts_at' => '2027-06-10 19:00:00', 'ends_at' => '2027-06-10 21:00:00']);
    $old = bmMeeting(['title' => 'Alt', 'starts_at' => '2026-12-01 19:00:00', 'ends_at' => '2026-12-01 21:00:00']);

    $this->post(route('board.resolutions.store', $meeting), ['title' => 'Haushaltsplan 2027', 'text' => 'Der Plan wird beschlossen.', 'outcome' => 'accepted', 'votes_for' => 4, 'votes_against' => 0, 'votes_abstain' => 1])->assertRedirect();
    $this->post(route('board.resolutions.store', $next), ['title' => 'Beitragsordnung', 'outcome' => 'postponed'])->assertRedirect();
    $this->post(route('board.resolutions.store', $old), ['title' => 'Vorjahr', 'outcome' => 'rejected'])->assertRedirect();

    expect(BoardResolution::query()->orderBy('id')->get()->map->number()->all())->toBe(['B-2027-001', 'B-2027-002', 'B-2026-001'])
        ->and(BoardResolution::query()->first()->votesLabel())->toBe('4 Ja, 0 Nein, 1 Enthaltung')
        ->and(AuditEntry::query()->where('event', 'board.resolution_saved')->count())->toBe(3);

    $this->get(route('board.meetings.show', $meeting))->assertOk()->assertSee('B-2027-001')->assertSee('Haushaltsplan 2027')->assertSee('Der Plan wird beschlossen.');
    $this->get(route('board.resolutions.index'))->assertOk()->assertSee('B-2026-001')->assertSee('Beitragsordnung');
    $this->get(route('board.resolutions.index', ['q' => 'Haushalt']))->assertOk()->assertSee('B-2027-001')->assertDontSee('Beitragsordnung');
    $this->get(route('board.resolutions.index', ['year' => 2026]))->assertOk()->assertSee('Vorjahr')->assertDontSee('Haushaltsplan');
    $this->get(route('board.resolutions.index', ['outcome' => 'postponed']))->assertOk()->assertSee('Beitragsordnung')->assertDontSee('Vorjahr');
});

it('edits and deletes resolutions and validates them', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $meeting = bmMeeting();
    $this->post(route('board.resolutions.store', $meeting), ['title' => '', 'outcome' => 'accepted'])->assertSessionHasErrors('title');
    $this->post(route('board.resolutions.store', $meeting), ['title' => 'X', 'outcome' => 'egal'])->assertSessionHasErrors('outcome');
    $this->post(route('board.resolutions.store', $meeting), ['title' => 'Erster', 'outcome' => 'accepted']);
    $resolution = BoardResolution::query()->firstOrFail();

    $this->get(route('board.resolutions.edit', $resolution))->assertOk()->assertSee('Erster');
    $this->put(route('board.resolutions.update', $resolution), ['title' => 'Geändert', 'outcome' => 'rejected'])->assertRedirect();
    expect($resolution->fresh()->title)->toBe('Geändert')->and($resolution->fresh()->outcome)->toBe('rejected');

    $this->post(route('board.resolutions.destroy-many'), ['ids' => [$resolution->id]])->assertRedirect();
    expect(BoardResolution::query()->count())->toBe(0);
});

function bmTask(array $override = []): Task
{
    $project = app(TaskProjects::class)->board();

    return Task::query()->create($override + ['task_project_id' => $project->id, 'task_status_id' => $project->statuses()->first()->id, 'title' => 'Aufgabe']);
}

it('creates tasks for a meeting in the project Vorstand with assignees and a resolution', function () {
    $a = bmUser(RoleKey::BoardMember, ['first_name' => 'Anna', 'last_name' => 'Eins']);
    $b = bmUser(RoleKey::BoardMember, ['first_name' => 'Bernd', 'last_name' => 'Zwei']);
    $outsider = bmUser(RoleKey::Coordination);
    $this->actingAs($a);
    $meeting = bmMeeting();
    $this->post(route('board.resolutions.store', $meeting), ['title' => 'Plan', 'outcome' => 'accepted']);
    $resolution = BoardResolution::query()->firstOrFail();
    $project = app(TaskProjects::class)->board();

    $this->post(route('tasks.store', $project), ['title' => 'Angebot einholen', 'board_meeting_id' => $meeting->id, 'board_resolution_id' => $resolution->id, 'assignees' => [$a->id, $b->id], 'due_on' => now()->subDay()->format('Y-m-d'), 'from_meeting' => 1])
        ->assertRedirect(route('board.meetings.show', $meeting).'#tasks-heading');
    $this->post(route('tasks.store', $project), ['title' => 'Fremd', 'assignees' => [$outsider->id]])->assertSessionHasErrors('assignees');
    $this->post(route('tasks.store', $project), ['title' => 'Falscher Beschluss', 'board_meeting_id' => bmMeeting(['title' => 'Andere'])->id, 'board_resolution_id' => $resolution->id])->assertSessionHasErrors('board_resolution_id');

    $task = Task::query()->where('title', 'Angebot einholen')->firstOrFail();
    expect($task->assignees)->toHaveCount(2)->and($task->isOverdue())->toBeTrue()->and($task->project->slug)->toBe('vorstand');
    $this->get(route('board.meetings.show', $meeting))->assertOk()->assertSee('Angebot einholen')->assertSee('Anna Eins, Bernd Zwei');
});

it('keeps tasks when their meeting or resolution is deleted', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $meeting = bmMeeting();
    $this->post(route('board.resolutions.store', $meeting), ['title' => 'Plan', 'outcome' => 'accepted']);
    $resolution = BoardResolution::query()->firstOrFail();
    $task = bmTask(['board_meeting_id' => $meeting->id, 'board_resolution_id' => $resolution->id]);

    $this->post(route('board.resolutions.destroy-many'), ['ids' => [$resolution->id]])->assertRedirect();
    expect($task->fresh()->board_resolution_id)->toBeNull()->and($task->fresh()->board_meeting_id)->toBe($meeting->id);

    $this->delete(route('board.meetings.destroy', $meeting));
    expect($task->fresh())->not->toBeNull()->and($task->fresh()->board_meeting_id)->toBeNull();
});

it('keeps resolutions from everyone but the board', function () {
    $resolution = BoardResolution::query()->create(['board_meeting_id' => bmMeeting()->id, 'year' => 2027, 'seq' => 1, 'title' => 'Intern', 'outcome' => 'accepted']);

    foreach ([RoleKey::AdministrationStaff, RoleKey::Coordination, RoleKey::Team, RoleKey::Member] as $role) {
        $this->actingAs(bmUser($role));
        $this->get(route('board.resolutions.index'))->assertForbidden();
        $this->get(route('board.resolutions.edit', $resolution))->assertForbidden();
        $this->post(route('board.resolutions.destroy-many'), ['ids' => [$resolution->id]])->assertForbidden();
    }

    expect(BoardResolution::query()->count())->toBe(1);
});

it('shows the board overview with the next meeting and open tasks', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $this->get(route('board.overview'))->assertOk()->assertSee('Vorstandsarbeit')->assertSee('keine geplant');

    bmMeeting(['starts_at' => now()->addDays(3)->setTime(19, 0), 'ends_at' => now()->addDays(3)->setTime(21, 0)]);
    bmTask(['title' => 'Offen', 'due_on' => now()->subDay()]);

    $this->get(route('board.overview'))->assertOk()->assertSee(now()->addDays(3)->format('d.m.Y').', 19:00 Uhr')->assertSee('Überfällige Aufgaben');
});
