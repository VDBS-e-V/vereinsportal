<?php

namespace App\Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Contracts\Models\SchoolContract;
use App\Modules\Contracts\Services\ContractSettings;
use App\Modules\Contracts\Services\SchoolContracts;
use App\Modules\Seminars\Models\Seminar;
use App\Support\Html\RichText;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Vertrag mit der Schule: erzeugen, drucken, Unterschrift erfassen, stornieren und die Vorlage pflegen (Berechtigung seminars.review). */
class SchoolContractController extends Controller
{
    public function __construct(private readonly SchoolContracts $contracts, private readonly ContractSettings $settings) {}

    public function show(Seminar $seminar): View
    {
        return view('school-contracts.show', ['seminar' => $seminar->load('partner'), 'contract' => $this->contracts->current($seminar)]);
    }

    public function store(Seminar $seminar): RedirectResponse
    {
        try {
            $this->contracts->create($seminar, $this->user());
        } catch (DomainException $exception) {
            return redirect()->route('school-contracts.show', $seminar)->with('warning', $exception->getMessage());
        }

        return redirect()->route('school-contracts.show', $seminar)->with('success', 'Der Vertrag mit der Schule wurde erzeugt. Prüfen Sie ihn und drucken Sie ihn aus.');
    }

    public function print(Seminar $seminar, SchoolContract $contract)
    {
        abort_unless($contract->seminar_id === $seminar->id, 404);

        return view('school-contracts.print', ['seminar' => $seminar, 'contract' => $contract, 'club' => $this->settings->all()]);
    }

    public function sign(Request $request, Seminar $seminar, SchoolContract $contract): RedirectResponse
    {
        abort_unless($contract->seminar_id === $seminar->id, 404);
        $data = $request->validate(['signed_on' => ['required', 'date', 'before_or_equal:today']], ['signed_on.required' => 'Bitte tragen Sie das Datum der Unterschrift ein.', 'signed_on.before_or_equal' => 'Das Datum der Unterschrift darf nicht in der Zukunft liegen.']);

        try {
            $this->contracts->recordSigned($contract, $data['signed_on'], $this->user());
        } catch (DomainException $exception) {
            return redirect()->route('school-contracts.show', $seminar)->with('warning', $exception->getMessage());
        }

        return redirect()->route('school-contracts.show', $seminar)->with('success', 'Die Unterschrift ist erfasst. Sie können das Seminar jetzt mit Basis „Vertrag“ freigeben.');
    }

    public function cancel(Seminar $seminar, SchoolContract $contract): RedirectResponse
    {
        abort_unless($contract->seminar_id === $seminar->id, 404);
        $this->contracts->cancel($contract, $this->user());

        return redirect()->route('school-contracts.show', $seminar)->with('success', 'Der Vertrag wurde storniert.');
    }

    public function template(Request $request): View
    {
        $versions = $this->contracts->versions();
        $shown = $request->filled('version') ? $versions->firstWhere('version', (int) $request->query('version')) : $versions->first();

        return view('school-contracts.template', ['versions' => $versions, 'shown' => $shown ?? $versions->first(), 'body' => RichText::forEditor(($shown ?? $versions->first())->body), 'placeholders' => SchoolContracts::PLACEHOLDERS]);
    }

    public function updateTemplate(Request $request): RedirectResponse
    {
        $request->merge(['body' => RichText::normalize($request->input('body'))]);
        $data = $request->validate(['title' => ['required', 'string', 'max:150'], 'body' => ['required', 'string', 'max:60000']], ['title.required' => 'Bitte geben Sie dem Vertrag einen Titel.', 'body.required' => 'Bitte schreiben Sie den Vertragstext.']);

        $unknown = $this->contracts->unknown($data['body']);

        if ($unknown !== []) {
            throw ValidationException::withMessages(['body' => 'Unbekannte Platzhalter '.implode(', ', array_map(fn (string $key): string => '{'.$key.'}', $unknown)).'.']);
        }

        $template = $this->contracts->createVersion($data['title'], $data['body'], $this->user());

        return redirect()->route('school-contracts.template')->with('success', 'Die Vorlage ist jetzt Version '.$template->version.'. Bestehende Verträge behalten ihre Fassung.');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
