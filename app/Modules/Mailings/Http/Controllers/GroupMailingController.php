<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\MailSnippet;
use App\Modules\Mailings\Models\MailTemplate;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Services\MailAttachments;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Services\MailTemplates;
use App\Modules\Mailings\Support\MailAccessibilityCheck;
use App\Modules\Mailings\Support\MailGroups;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Support\Html\RichText;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
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
        $user = $this->user();
        $kinds = [Mailing::KIND_GENERAL, Mailing::KIND_MEMBERS];
        $preparing = [Mailing::STATUS_DRAFT, Mailing::STATUS_PENDING, Mailing::STATUS_SCHEDULED];

        return view('mailings.groups-index', [
            'base' => $this->base($request),
            'area' => $this->area($request),
            'mailings' => Mailing::query()->whereIn('kind', $kinds)->whereNotIn('status', $preparing)->with('sentBy.person')->orderByDesc('id')->paginate(15),
            // In Vorbereitung: die eigenen Entwürfe und Vorlagen zur Freigabe sowie, wer freigeben darf, alle vorgelegten und geplanten Schreiben
            'preparing' => Mailing::query()->whereIn('kind', $kinds)->whereIn('status', $preparing)
                ->where(fn ($query) => $query->where('sent_by_user_id', $user->id)->orWhere(fn ($other) => $other->whereIn('status', [Mailing::STATUS_PENDING, Mailing::STATUS_SCHEDULED])->whereRaw('1 = ?', [$user->can('mailings.approve') ? 1 : 0])))
                ->with('sentBy.person')->orderByDesc('id')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $this->user();
        $allowed = MailGroups::allowedFor($user);
        $template = null;
        $draft = null;

        if ($request->filled('vorlage')) {
            $template = MailTemplate::query()->where('kind', MailTemplate::KIND_GENERAL)->find((int) $request->query('vorlage'));
        }

        if ($request->filled('entwurf')) {
            $draft = Mailing::query()->where('status', Mailing::STATUS_DRAFT)->where('sent_by_user_id', $user->id)->find((int) $request->query('entwurf'));
        }

        $prefill = $this->prefill($draft, $template);
        $selected = array_values(array_intersect((array) old('groups', $prefill['groups'] ?? []), array_keys($allowed)));
        $excluded = array_values(array_intersect((array) old('exclude', $prefill['exclude'] ?? []), array_keys($allowed)));
        $peopleTokens = $this->recipients->allowedTokens((array) old('people', $prefill['people'] ?? $this->contextPeople($request)), $user);
        $chips = $this->recipients->describe($peopleTokens);

        $options = [];

        foreach ($allowed as $key => $group) {
            $options[$key] = $group['label'].' ('.count($this->recipients->groups([$key])).')';
        }

        $canMandatory = $user->can('mailings.members');
        $mandatory = $canMandatory && (bool) old('mandatory', $prefill['mandatory'] ?? false);
        $stats = $selected === [] && $chips === [] ? ['kept' => [], 'unsubscribed' => 0, 'suppressed' => 0, 'excluded' => 0] : $this->recipients->groupsWithStats($selected, $mandatory, $excluded, array_column($chips, 'token'));
        $total = count($stats['kept']);

        return view('mailings.groups-form', [
            'base' => $this->base($request),
            'area' => $this->area($request),
            'groupOptions' => $options,
            'selected' => $selected,
            'excludedGroups' => $excluded,
            'excludeOptions' => array_map(fn (array $group): string => $group['label'], $allowed),
            'chips' => $chips,
            'peopleOptions' => array_column($chips, 'label', 'token'),
            'excludedCount' => $stats['excluded'],
            'total' => $total,
            'unsubscribedCount' => $stats['unsubscribed'],
            'suppressedCount' => $stats['suppressed'],
            'canMandatory' => $canMandatory,
            'mandatoryChecked' => $mandatory,
            'needsApproval' => $this->mailings->needsApproval($user, $total),
            'placeholders' => MailPlaceholders::definitions('general'),
            'templates' => $this->templates->general()->mapWithKeys(fn (MailTemplate $t): array => [$t->id => ($t->category ? \App\Modules\Mailings\Support\MailCategories::templateLabel($t->category).': ' : '').$t->name])->all(),
            'template' => $template,
            'draft' => $draft,
            'prefill' => $prefill,
            'snippets' => MailSnippet::query()->orderBy('category')->orderBy('sort')->orderBy('name')->get(['id', 'name', 'category', 'body']),
            'attachments' => $draft?->attachments ?? collect(),
            'attachmentLimitMb' => round(app(MailAttachments::class)->maxKb() / 1024, 1),
        ]);
    }

    /** Kontextstart: ?schule=ID belegt die Empfänger mit den Ansprechpersonen der Schule vor (nur mit Recht auf Schulen und Schreiben). @return list<string> */
    private function contextPeople(Request $request): array
    {
        if (! $request->filled('schule') || ! $this->user()->can('partners.view') || ! $this->user()->can('mailings.send')) {
            return [];
        }

        return $this->recipients->contactTokens((int) $request->query('schule'));
    }

    /** Suche für die Einzelauswahl (JSON): Konten und Mitglieder, soweit die Person sie anschreiben darf. */
    public function people(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->recipients->search((string) $request->query('q', ''), $this->user())]);
    }

    /**
     * Vorbelegung des Formulars aus einem Entwurf, sonst aus einer Vorlage.
     *
     * @return array<string, mixed>
     */
    private function prefill(?Mailing $draft, ?MailTemplate $template): array
    {
        if ($draft !== null) {
            $meeting = (array) (($draft->payload ?? [])['meeting'] ?? []);

            return [
                'subject' => $draft->subject, 'body' => RichText::forEditor($draft->body), 'groups' => (array) $draft->groups, 'exclude' => $draft->excludedGroups(), 'people' => $draft->chosenPeople(), 'mandatory' => (bool) $draft->mandatory,
                'send_at' => $draft->send_at?->format('Y-m-d\TH:i'),
                'meeting_title' => $meeting['title'] ?? null, 'meeting_date' => $meeting['date'] ?? null, 'meeting_time' => $meeting['time'] ?? null,
                'meeting_place' => $meeting['place'] ?? null, 'meeting_link' => $meeting['link'] ?? null, 'meeting_deadline' => $meeting['deadline'] ?? null,
                'rejection_note' => $draft->rejection_note,
            ];
        }

        return $template !== null ? ['subject' => $template->subject, 'body' => RichText::forEditor($template->body)] : [];
    }

    public function preview(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $preview = $this->mailings->previewGroups($data['subject'], $data['body'], $this->meeting($data), $this->user());

        $notice = $this->mailings->noticeWarning(array_values($data['groups']), $this->meeting($data), $this->sendAt($data));

        return redirect()->route($this->base($request).'.create')->withInput($request->all())->with('preview', $preview)->with('notice', $notice)->with('hints', MailAccessibilityCheck::check($data['body']));
    }

    public function test(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->mailings->sendGroupsTest($data['subject'], $data['body'], $this->meeting($data), $this->user());

        return redirect()->route($this->base($request).'.create')->withInput($request->all())->with('success', 'Die Testmail wurde an Ihre Adresse gesendet.');
    }

    /** Speichert das Formular als Entwurf (ohne zu senden) und öffnet ihn wieder. */
    public function draft(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $mailing = $this->mailings->saveDraft(['groups' => array_values($data['groups']), 'subject' => $data['subject'], 'body' => $data['body'], 'meeting' => $this->meeting($data), 'mandatory' => $data['mandatory'], 'send_at' => $this->sendAt($data), 'exclude' => $data['exclude'], 'people' => $data['people']], $this->user(), $this->areaKey($request), $this->ownDraft($request));

        return redirect()->route($this->base($request).'.create', ['entwurf' => $mailing->id])->with('success', $this->mailings->flashMessage($mailing));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $mailing = $this->mailings->sendGroups(array_values($data['groups']), $data['subject'], $data['body'], $this->meeting($data), $this->user(), $data['mandatory'], $this->sendAt($data), $this->areaKey($request), $this->ownDraft($request), $data['exclude'], $data['people']);
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
            'groups' => ['nullable', 'array'],
            'groups.*' => ['string', Rule::in($allowed)],
            'exclude' => ['nullable', 'array'],
            'exclude.*' => ['string', Rule::in($allowed)],
            'people' => ['nullable', 'array', 'max:200'],
            'people.*' => ['string', 'regex:/^([umk]:\d+|e:[^\s@]+@[^\s@]+)$/'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:20000'],
            'meeting_title' => ['nullable', 'string', 'max:150'],
            'meeting_date' => ['nullable', 'date'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'meeting_place' => ['nullable', 'string', 'max:200'],
            'meeting_link' => ['nullable', 'url', 'max:300'],
            'meeting_deadline' => ['nullable', 'date'],
            'mandatory' => ['nullable', 'boolean'],
            'send_at' => ['nullable', 'date', 'after:now'],
            'draft_id' => ['nullable', 'integer'],
        ], [
            'send_at.after' => 'Der Versandzeitpunkt muss in der Zukunft liegen.',
            'send_at.date' => 'Bitte geben Sie einen gültigen Zeitpunkt an.',
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

        $data['groups'] = array_values($data['groups'] ?? []);
        $data['exclude'] = array_values($data['exclude'] ?? []);
        $data['people'] = array_column($this->recipients->describe($this->recipients->allowedTokens($data['people'] ?? [], $this->user())), 'token');

        if ($data['groups'] === [] && $data['people'] === []) {
            throw ValidationException::withMessages(['groups' => 'Bitte wählen Sie mindestens eine Gruppe oder eine Person.']);
        }

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

    /** Der geplante Versandzeitpunkt, falls angegeben. @param  array<string, mixed>  $data */
    private function sendAt(array $data): ?Carbon
    {
        return filled($data['send_at'] ?? null) ? Carbon::parse((string) $data['send_at']) : null;
    }

    /** Der eigene Entwurf, an dem das Formular gerade arbeitet (draft_id), sonst null. */
    private function ownDraft(Request $request): ?Mailing
    {
        if (! $request->filled('draft_id')) {
            return null;
        }

        return Mailing::query()->where('status', Mailing::STATUS_DRAFT)->where('sent_by_user_id', $this->user()->id)->find((int) $request->input('draft_id'));
    }

    /** board oder office. */
    private function areaKey(Request $request): string
    {
        return Str::afterLast($this->base($request), '.');
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
