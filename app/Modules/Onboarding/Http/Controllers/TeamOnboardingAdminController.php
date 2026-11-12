<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Onboarding\Enums\OnboardingDocumentKind;
use App\Modules\Onboarding\Enums\TeamStep;
use App\Modules\Onboarding\Models\TeamOnboarding;
use App\Modules\Onboarding\Services\OnboardingDocuments;
use App\Modules\Onboarding\Services\TeamOnboardings;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Onboarding der Teamenden aus Sicht der Verwaltung (Bereich Koordination): Übersicht, Schritte eintragen, Dokumente der Teamenden. */
class TeamOnboardingAdminController extends Controller
{
    public function __construct(
        private readonly TeamOnboardings $onboardings,
        private readonly OnboardingDocuments $documents,
    ) {}

    public function index(Request $request): View
    {
        $this->onboardings->ensureAll($this->actor($request));

        $onboardings = TeamOnboarding::query()->with(['user.person', 'steps'])->orderByRaw('completed_at IS NOT NULL')->orderByDesc('started_at')->orderByDesc('id')->get();

        return view('onboarding.team-index', [
            'onboardings' => $onboardings,
            'stats' => ['total' => $onboardings->count(), 'open' => $onboardings->whereNull('completed_at')->count(), 'done' => $onboardings->whereNotNull('completed_at')->count()],
            'canManage' => Gate::allows('team.manage'),
        ]);
    }

    public function show(User $user): View
    {
        $onboarding = $this->onboardings->ensure($user);

        abort_unless($onboarding instanceof TeamOnboarding, 404);

        $onboarding->load(['user.person', 'steps.doneBy.person', 'steps.document']);

        return view('onboarding.team-show', [
            'onboarding' => $onboarding,
            'steps' => TeamStep::cases(),
            'currentDocuments' => collect(OnboardingDocumentKind::forTeam())->mapWithKeys(fn (OnboardingDocumentKind $kind): array => [$kind->value => $this->documents->current($kind)])->all(),
            'canManage' => Gate::allows('team.manage'),
        ]);
    }

    public function mark(Request $request, User $user, string $step): RedirectResponse
    {
        $stepEnum = TeamStep::tryFrom($step);

        abort_if($stepEnum === null, 404);

        $onboarding = $this->onboardings->ensure($user);

        abort_unless($onboarding instanceof TeamOnboarding, 404);

        $needsDate = $stepEnum->dateLabel() !== null;

        $data = $request->validate([
            'date' => [$needsDate ? 'required' : 'nullable', 'date_format:Y-m-d', 'after_or_equal:1950-01-01', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'date.required' => 'Bitte geben Sie das Datum an.',
            'date.date_format' => 'Bitte geben Sie ein gültiges Datum an.',
            'date.after_or_equal' => 'Bitte prüfen Sie das Datum.',
            'date.before_or_equal' => 'Das Datum darf nicht in der Zukunft liegen.',
            'note.max' => 'Die Notiz darf höchstens 500 Zeichen lang sein.',
        ]);

        $this->onboardings->markDone($onboarding, $stepEnum, $this->actor($request), $data['date'] ?? null, $data['note'] ?? null);

        return redirect()->route('team-onboarding.show', $user)->with('success', 'Der Schritt „'.$stepEnum->label().'“ wurde eingetragen.');
    }

    public function reopen(Request $request, User $user, string $step): RedirectResponse
    {
        $stepEnum = TeamStep::tryFrom($step);

        abort_if($stepEnum === null, 404);

        $onboarding = $this->onboardings->ensure($user);

        abort_unless($onboarding instanceof TeamOnboarding, 404);

        $this->onboardings->reopen($onboarding, $stepEnum, $this->actor($request));

        return redirect()->route('team-onboarding.show', $user)->with('success', 'Der Schritt „'.$stepEnum->label().'“ ist wieder offen.');
    }

    public function documents(): View
    {
        return view('onboarding.team-documents', [
            'kinds' => OnboardingDocumentKind::forTeam(),
            'documents' => collect(OnboardingDocumentKind::forTeam())->mapWithKeys(fn (OnboardingDocumentKind $kind): array => [$kind->value => $this->documents->history($kind)])->all(),
            'canManage' => Gate::allows('team.manage'),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_map(fn (OnboardingDocumentKind $kind): string => $kind->value, OnboardingDocumentKind::forTeam()))],
            'document' => ['required', 'file', 'mimes:pdf', 'max:'.OnboardingDocuments::MAX_KILOBYTES],
        ], [
            'kind.required' => 'Bitte wählen Sie das Dokument.',
            'kind.in' => 'Bitte wählen Sie ein Dokument aus der Liste.',
            'document.required' => 'Bitte wählen Sie eine PDF-Datei aus.',
            'document.mimes' => 'Erlaubt sind nur PDF-Dateien.',
            'document.max' => 'Die Datei darf höchstens 10 MB groß sein.',
            'document.file' => 'Bitte wählen Sie eine PDF-Datei aus.',
        ]);

        try {
            $this->documents->store($request->file('document'), OnboardingDocumentKind::from($data['kind']), $this->actor($request));
        } catch (DomainException $exception) {
            return redirect()->route('team-onboarding.documents')->with('warning', $exception->getMessage());
        }

        return redirect()->route('team-onboarding.documents')->with('success', 'Das Dokument wurde gespeichert. Teamende bestätigen ab jetzt diese Fassung.');
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
