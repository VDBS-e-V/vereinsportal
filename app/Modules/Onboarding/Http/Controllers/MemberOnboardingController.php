<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Onboarding\Enums\OnboardingDocumentKind;
use App\Modules\Onboarding\Services\MemberWelcome;
use App\Modules\Onboarding\Services\OnboardingDocuments;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Onboarding der Mitglieder (Bereich Vorstand): Dokumente des Willkommenspakets und erneutes Senden an eine Mitgliedschaft. */
class MemberOnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingDocuments $documents,
        private readonly MemberWelcome $welcome,
    ) {}

    public function index(): View
    {
        return view('onboarding.member', [
            'kinds' => OnboardingDocumentKind::forMembers(),
            'documents' => collect(OnboardingDocumentKind::forMembers())->mapWithKeys(fn (OnboardingDocumentKind $kind): array => [$kind->value => $this->documents->history($kind)])->all(),
            'canManage' => Gate::allows('memberships.manage'),
            'autoSend' => (bool) config('portal.onboarding.welcome_on_admission'),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_map(fn (OnboardingDocumentKind $kind): string => $kind->value, OnboardingDocumentKind::forMembers()))],
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
            return redirect()->route('onboarding.member')->with('warning', $exception->getMessage());
        }

        return redirect()->route('onboarding.member')->with('success', 'Das Dokument wurde gespeichert und gilt ab jetzt für neue Willkommenspakete.');
    }

    /** Sendet das Willkommenspaket erneut (oder erstmals, z. B. bei vom Vorstand erfassten Mitgliedern). */
    public function send(Request $request, Membership $membership): RedirectResponse
    {
        if ($membership->effectiveStatus() !== MembershipStatus::Active) {
            return redirect()->route('membership.show', $membership)->with('warning', 'Das Willkommenspaket geht nur an laufende Mitgliedschaften.');
        }

        try {
            $count = $this->welcome->send($membership, $this->actor($request));
        } catch (DomainException $exception) {
            return redirect()->route('membership.show', $membership)->with('warning', $exception->getMessage());
        }

        return redirect()->route('membership.show', $membership)->with('success', 'Das Willkommenspaket wurde an '.$membership->contactEmail().' gesendet'.($count > 0 ? ' ('.$count.' Anhang'.($count === 1 ? '' : 'e').').' : ' (ohne Anhang, es sind noch keine Dokumente hochgeladen).'));
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
