<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\MailTemplate;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Services\MailTemplates;
use App\Modules\Mailings\Support\MailGroups;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Support\Html\RichText;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Schreiben an Gruppen (Vorstand und Geschäftsstelle): frei formuliert mit Platzhaltern oder auf Basis einer Vorlage. Eine Seite je Bereich
 * (mailings.board.*, mailings.office.*), beide nutzen diese Klasse. Erst Vorschau, dann Senden. Mitglieder darf nur anschreiben, wer mailings.members hat.
 */
class GroupMailingController extends Controller
{
    public function __construct(private readonly Mailings $mailings, private readonly MailRecipients $recipients, private readonly MailTemplates $templates) {}

    public function index(Request $request): View
    {
        return view('mailings.groups-index', [
            'base' => $this->base($request),
            'area' => $this->area($request),
            'mailings' => Mailing::query()->whereIn('kind', [Mailing::KIND_GENERAL, Mailing::KIND_MEMBERS])->with('sentBy.person')->orderByDesc('id')->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $allowed = MailGroups::allowedFor($this->user());
        $selected = array_values(array_intersect((array) old('groups', []), array_keys($allowed)));
        $template = null;

        if ($request->filled('vorlage')) {
            $template = MailTemplate::query()->where('kind', MailTemplate::KIND_GENERAL)->find((int) $request->query('vorlage'));
        }

        $options = [];

        foreach ($allowed as $key => $group) {
            $options[$key] = ['label' => $group['label'].' ('.count($this->recipients->groups([$key])).')', 'hint' => $group['description']];
        }

        $canMandatory = $this->user()->can('mailings.members');
        $mandatory = $canMandatory && (bool) old('mandatory');
        $stats = $selected === [] ? ['kept' => [], 'unsubscribed' => 0, 'suppressed' => 0] : $this->recipients->groupsWithStats($selected, $mandatory);

        return view('mailings.groups-form', [
            'base' => $this->base($request),
            'area' => $this->area($request),
            'groupOptions' => $options,
            'selected' => $selected,
            'total' => count($stats['kept']),
            'unsubscribedCount' => $stats['unsubscribed'],
            'suppressedCount' => $stats['suppressed'],
            'canMandatory' => $canMandatory,
            'placeholders' => MailPlaceholders::definitions('general'),
            'templates' => $this->templates->general()->mapWithKeys(fn (MailTemplate $t): array => [$t->id => $t->name])->all(),
            'template' => $template,
            'prefillBody' => $template ? RichText::forEditor($template->body) : null,
        ]);
    }

    public function preview(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $preview = $this->mailings->previewGroups($data['subject'], $data['body'], $this->meeting($data), $this->user());

        return redirect()->route($this->base($request).'.create')->withInput($request->all())->with('preview', $preview);
    }

    public function test(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->mailings->sendGroupsTest($data['subject'], $data['body'], $this->meeting($data), $this->user());

        return redirect()->route($this->base($request).'.create')->withInput($request->all())->with('success', 'Die Testmail wurde an Ihre Adresse gesendet.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $mailing = $this->mailings->sendGroups(array_values($data['groups']), $data['subject'], $data['body'], $this->meeting($data), $this->user(), $data['mandatory']);
        } catch (DomainException $exception) {
            return redirect()->route($this->base($request).'.create')->withInput($request->all())->with('error', $exception->getMessage());
        }

        return redirect()->route($this->base($request).'.show', $mailing)->with('success', $this->mailings->flashMessage($mailing));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge(['body' => RichText::normalize($request->input('body'))]);
        $allowed = array_keys(MailGroups::allowedFor($this->user()));

        $data = $request->validate([
            'groups' => ['required', 'array', 'min:1'],
            'groups.*' => ['string', Rule::in($allowed)],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:20000'],
            'meeting_title' => ['nullable', 'string', 'max:150'],
            'meeting_date' => ['nullable', 'date'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'meeting_place' => ['nullable', 'string', 'max:200'],
            'meeting_link' => ['nullable', 'url', 'max:300'],
            'meeting_deadline' => ['nullable', 'date'],
            'mandatory' => ['nullable', 'boolean'],
        ], [
            'groups.required' => 'Bitte wählen Sie mindestens eine Gruppe.',
            'groups.min' => 'Bitte wählen Sie mindestens eine Gruppe.',
            'groups.*.in' => 'Diese Gruppe dürfen Sie nicht anschreiben.',
            'subject.required' => 'Bitte geben Sie einen Betreff ein.',
            'body.required' => 'Bitte schreiben Sie den Text.',
            'body.max' => 'Der Text ist zu lang.',
            'meeting_date.date' => 'Bitte geben Sie ein gültiges Datum ein.',
            'meeting_time.date_format' => 'Bitte geben Sie die Uhrzeit als HH:MM an.',
            'meeting_link.url' => 'Bitte geben Sie eine vollständige Adresse mit https:// an.',
            'meeting_deadline.date' => 'Bitte geben Sie ein gültiges Datum ein.',
        ]);

        // Pflichtmitteilungen (nicht abbestellbar) darf nur schreiben, wer auch die Mitglieder anschreiben darf
        $data['mandatory'] = $this->user()->can('mailings.members') && (bool) ($data['mandatory'] ?? false);

        $errors = [];

        foreach (['subject' => 'Betreff', 'body' => 'Text'] as $field => $label) {
            $unknown = MailPlaceholders::unknown($data[$field], 'general');

            if ($unknown !== []) {
                $errors[$field] = $label.': Unbekannte Platzhalter '.implode(', ', array_map(fn (string $key): string => '{'.$key.'}', $unknown)).'.';
            }
        }

        $missing = $this->mailings->missingMeetingFields($data['subject'], $data['body'], $this->meeting($data));

        if ($missing !== []) {
            $errors['body'] = trim(($errors['body'] ?? '').' Zu '.implode(', ', array_map(fn (string $key): string => '{'.$key.'}', $missing)).' fehlt die Angabe zum Termin.');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
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

    /** Routenpräfix der aufrufenden Seite: mailings.board oder mailings.office. */
    private function base(Request $request): string
    {
        return Str::beforeLast((string) $request->route()?->getName(), '.');
    }

    private function area(Request $request): string
    {
        return $this->base($request) === 'mailings.board' ? 'Vorstand' : 'Geschäftsstelle';
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
