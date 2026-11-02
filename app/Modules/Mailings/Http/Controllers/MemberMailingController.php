<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Support\Html\RichText;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Rundschreiben des Vorstands an alle Mitglieder (z. B. Einladung zur Versammlung): frei formuliert, mit Platzhaltern, erst Vorschau, dann Senden. */
class MemberMailingController extends Controller
{
    public function __construct(private readonly Mailings $mailings, private readonly MailRecipients $recipients) {}

    public function index(): View
    {
        return view('mailings.members-index', [
            'mailings' => Mailing::query()->where('kind', Mailing::KIND_MEMBERS)->with('sentBy.person')->orderByDesc('id')->paginate(15),
            'count' => count($this->recipients->members()),
        ]);
    }

    public function create(): View
    {
        return view('mailings.members-form', [
            'placeholders' => MailPlaceholders::definitions('members'),
            'count' => count($this->recipients->members()),
        ]);
    }

    public function preview(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $preview = $this->mailings->previewMembers($data['subject'], $data['body'], $this->meeting($data), $this->user());

        return redirect()->route('mailings.members.create')->withInput($request->all())->with('preview', $preview);
    }

    public function test(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->mailings->sendMembersTest($data['subject'], $data['body'], $this->meeting($data), $this->user());

        return redirect()->route('mailings.members.create')->withInput($request->all())->with('success', 'Die Testmail wurde an Ihre Adresse gesendet.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $mailing = $this->mailings->sendMembers($data['subject'], $data['body'], $this->meeting($data), $this->user());
        } catch (DomainException $exception) {
            return redirect()->route('mailings.members.create')->withInput($request->all())->with('error', $exception->getMessage());
        }

        $note = $mailing->failed_count > 0 ? ' '.$mailing->failed_count.' Zustellungen sind fehlgeschlagen (siehe Fehlerprotokoll).' : '';

        return redirect()->route('mailings.members.index')->with('success', 'Das Schreiben wurde an '.$mailing->recipients_count.' Mitglieder gesendet.'.$note);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge(['body' => RichText::normalize($request->input('body'))]);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:20000'],
            'meeting_title' => ['nullable', 'string', 'max:150'],
            'meeting_date' => ['nullable', 'date'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'meeting_place' => ['nullable', 'string', 'max:200'],
            'meeting_link' => ['nullable', 'url', 'max:300'],
            'meeting_deadline' => ['nullable', 'date'],
        ], [
            'subject.required' => 'Bitte geben Sie einen Betreff ein.',
            'body.required' => 'Bitte schreiben Sie den Text.',
            'body.max' => 'Der Text ist zu lang.',
            'meeting_date.date' => 'Bitte geben Sie ein gültiges Datum ein.',
            'meeting_time.date_format' => 'Bitte geben Sie die Uhrzeit als HH:MM an.',
            'meeting_link.url' => 'Bitte geben Sie eine vollständige Adresse mit https:// an.',
            'meeting_deadline.date' => 'Bitte geben Sie ein gültiges Datum ein.',
        ]);

        $errors = [];

        foreach (['subject' => 'Betreff', 'body' => 'Text'] as $field => $label) {
            $unknown = MailPlaceholders::unknown($data[$field], 'members');

            if ($unknown !== []) {
                $errors[$field] = $label.': Unbekannte Platzhalter '.implode(', ', array_map(fn (string $key): string => '{'.$key.'}', $unknown)).'.';
            }
        }

        $missing = $this->mailings->missingMeetingFields($data['subject'], $data['body'], $this->meeting($data));

        if ($missing !== []) {
            $errors['body'] = ($errors['body'] ?? '').' Zu '.implode(', ', array_map(fn (string $key): string => '{'.$key.'}', $missing)).' fehlt die Angabe zur Versammlung.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(array_map('trim', $errors));
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function meeting(array $data): array
    {
        return ['title' => $data['meeting_title'] ?? null, 'date' => $data['meeting_date'] ?? null, 'time' => $data['meeting_time'] ?? null, 'place' => $data['meeting_place'] ?? null, 'link' => $data['meeting_link'] ?? null, 'deadline' => $data['meeting_deadline'] ?? null];
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
