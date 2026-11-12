<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Mailings\Models\MailSegment;
use App\Modules\Mailings\Services\SegmentResolver;
use App\Modules\Mailings\Support\MailGroups;
use App\Modules\Mailings\Support\SegmentFilters;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Verteiler (Geschäftsstelle, Vorstand): benannte, gespeicherte Empfängerkreise aus Gruppen, Ausschlüssen und Filtern.
 * Gespeichert wird die Regel, nicht die Adressen. Mitglieder-Verteiler dürfen nur anlegen und ändern, die Mitglieder anschreiben dürfen.
 */
class MailSegmentController extends Controller
{
    public function __construct(private readonly SegmentResolver $resolver) {}

    public function index(): View
    {
        $segments = MailSegment::query()->orderBy('name')->get()->map(fn (MailSegment $segment): array => [
            'segment' => $segment,
            'count' => count($this->resolver->recipients($segment)['kept']),
        ]);

        return view('mailings.segments-index', ['rows' => $segments, 'user' => $this->user()]);
    }

    public function create(): View
    {
        return $this->form(null, new MailSegment(['definition' => [], 'visible_to' => ['office']]));
    }

    public function edit(MailSegment $segment): View
    {
        $this->authorizeSegment($segment);

        return $this->form($segment, $segment, $this->previewOf($segment));
    }

    /** Vorschau mit den Eingaben des Formulars, ohne zu speichern. */
    public function preview(Request $request, ?MailSegment $segment = null): View
    {
        $draft = new MailSegment($this->validated($request, $segment));

        return $this->form($segment, $draft, $this->previewOf($draft));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $segment = MailSegment::query()->create($data + ['created_by_user_id' => $this->user()->id, 'updated_by_user_id' => $this->user()->id]);
        AuditLog::record(AuditEvent::MailSegmentSaved, $this->user(), null, [], 'Verteiler angelegt');

        return redirect()->route('mailings.segments.edit', $segment)->with('success', 'Der Verteiler „'.$segment->name.'“ wurde gespeichert.');
    }

    public function update(Request $request, MailSegment $segment): RedirectResponse
    {
        $this->authorizeSegment($segment);
        $segment->forceFill($this->validated($request, $segment) + ['updated_by_user_id' => $this->user()->id])->save();
        AuditLog::record(AuditEvent::MailSegmentSaved, $this->user(), null, [], 'Verteiler geändert');

        return redirect()->route('mailings.segments.edit', $segment)->with('success', 'Der Verteiler wurde gespeichert.');
    }

    public function destroy(MailSegment $segment): RedirectResponse
    {
        $this->authorizeSegment($segment);
        $segment->delete();
        AuditLog::record(AuditEvent::MailSegmentSaved, $this->user(), null, [], 'Verteiler gelöscht');

        return redirect()->route('mailings.segments.index')->with('success', 'Der Verteiler wurde gelöscht.');
    }

    /** Mehrfachauswahl: löscht die angehakten Verteiler (nur die, die diese Person ändern darf). */
    public function destroyMany(Request $request): RedirectResponse
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        $count = 0;

        foreach (MailSegment::query()->whereIn('id', $ids)->get() as $segment) {
            if ($this->mayManage($segment)) {
                $segment->delete();
                $count++;
            }
        }

        return redirect()->route('mailings.segments.index')->with($count > 0 ? 'success' : 'error', $count > 0 ? $count.' Verteiler gelöscht.' : 'Bitte wählen Sie mindestens einen Verteiler, den Sie ändern dürfen.');
    }

    /** @param  array<string, mixed>|null  $preview */
    private function form(?MailSegment $existing, MailSegment $segment, ?array $preview = null): View
    {
        $groups = array_map(fn (array $group): string => $group['label'], MailGroups::allowedFor($this->user()));

        return view('mailings.segments-form', [
            'existing' => $existing,
            'segment' => $segment,
            'filters' => $segment->filters(),
            'groupOptions' => $groups,
            'areaOptions' => MailSegment::AREAS,
            'federalStates' => SegmentFilters::federalStates(),
            'schoolTypes' => SegmentFilters::schoolTypes(),
            'ageGroups' => array_combine(array_keys(SegmentFilters::AGE_GROUPS), array_map(fn (string $key): string => $key.' Jahre', array_keys(SegmentFilters::AGE_GROUPS))),
            'seminars' => Seminar::query()->orderByDesc('id')->limit(300)->get()->mapWithKeys(fn (Seminar $seminar): array => [$seminar->id => trim(($seminar->number ? $seminar->number.' · ' : '').$seminar->title)])->all(),
            'preview' => $preview,
        ]);
    }

    /** @return array{count: int, names: list<string>, unsubscribed: int, suppressed: int} */
    private function previewOf(MailSegment $segment): array
    {
        $result = $this->resolver->recipients($segment);
        $names = collect($result['kept'])->take(10)->map(fn (array $entry): string => trim(($entry['first'] ?? '').' '.($entry['last'] ?? '')) ?: '(ohne Namen)')->all();

        return ['count' => count($result['kept']), 'names' => $names, 'unsubscribed' => $result['unsubscribed'], 'suppressed' => $result['suppressed']];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?MailSegment $existing): array
    {
        $allowed = array_keys(MailGroups::allowedFor($this->user()));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('mail_segments', 'name')->ignore($existing?->id)],
            'description' => ['nullable', 'string', 'max:250'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['string', Rule::in($allowed)],
            'exclude' => ['nullable', 'array'],
            'exclude.*' => ['string', Rule::in($allowed)],
            'visible_to' => ['required', 'array', 'min:1'],
            'visible_to.*' => ['string', Rule::in(array_keys(MailSegment::AREAS))],
        ], [
            'name.required' => 'Bitte geben Sie dem Verteiler einen Namen.',
            'name.unique' => 'Es gibt schon einen Verteiler mit diesem Namen.',
            'groups.*.in' => 'Diese Gruppe dürfen Sie nicht verwenden.',
            'exclude.*.in' => 'Diese Gruppe dürfen Sie nicht verwenden.',
            'visible_to.required' => 'Bitte wählen Sie, wer den Verteiler nutzen darf.',
            'visible_to.min' => 'Bitte wählen Sie, wer den Verteiler nutzen darf.',
        ]);

        $groups = array_values($data['groups'] ?? []);
        $filters = SegmentFilters::normalize($request->all());

        if ($groups === [] && ! isset($filters['seminar_id'])) {
            throw ValidationException::withMessages(['groups' => 'Bitte wählen Sie mindestens eine Gruppe.']);
        }

        // Verteiler mit Mitgliedern oder Pflichtmitteilungen gehören dem Vorstand; die Koordination darf nur ihre vier Gruppen nutzen
        $visible = array_values($data['visible_to']);

        if (array_diff($groups, MailGroups::LIMITED) !== []) {
            $visible = array_values(array_diff($visible, ['coordination']));
        }

        if ($visible === []) {
            throw ValidationException::withMessages(['visible_to' => 'Mit diesen Gruppen darf die Koordination den Verteiler nicht nutzen. Wählen Sie einen anderen Bereich.']);
        }

        return [
            'name' => trim($data['name']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'definition' => ['groups' => $groups, 'exclude' => array_values($data['exclude'] ?? []), 'filters' => $filters, 'people' => $existing?->people() ?? []],
            'visible_to' => $visible,
        ];
    }

    private function mayManage(MailSegment $segment): bool
    {
        return in_array('members', $segment->groups(), true) ? $this->user()->can('mailings.members') : true;
    }

    private function authorizeSegment(MailSegment $segment): void
    {
        abort_unless($this->mayManage($segment), 403);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
