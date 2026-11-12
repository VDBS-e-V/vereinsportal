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

/** Die eigene Einarbeitung der Teamenden (Bereich Teamende): Stand ansehen und Erklärungen bestätigen. */
class TeamSelfController extends Controller
{
    public function __construct(
        private readonly TeamOnboardings $onboardings,
        private readonly OnboardingDocuments $documents,
    ) {}

    public function index(Request $request): View
    {
        $onboarding = $this->onboardings->ensure($this->actor($request));

        abort_unless($onboarding instanceof TeamOnboarding, 404);

        $onboarding->load('steps.doneBy.person');

        return view('onboarding.team-self', [
            'onboarding' => $onboarding,
            'steps' => TeamStep::cases(),
            'currentDocuments' => collect(OnboardingDocumentKind::forTeam())->mapWithKeys(fn (OnboardingDocumentKind $kind): array => [$kind->value => $this->documents->current($kind)])->all(),
        ]);
    }

    public function confirm(Request $request, string $step): RedirectResponse
    {
        $stepEnum = TeamStep::tryFrom($step);

        abort_if($stepEnum === null, 404);

        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Bitte bestätigen Sie, dass Sie das Dokument gelesen haben.']);

        try {
            $this->onboardings->confirmOwn($this->actor($request), $stepEnum);
        } catch (DomainException $exception) {
            return redirect()->route('team-self.index')->with('warning', $exception->getMessage());
        }

        return redirect()->route('team-self.index')->with('success', 'Danke, „'.$stepEnum->label().'“ ist bestätigt.');
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
