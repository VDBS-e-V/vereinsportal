<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Enums\PartnerType;
use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Services\SeminarWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Koordination und Geschäftsstelle tragen eine Seminaranfrage im Namen einer Schule ein (z. B. nach einem Anruf). */
class EnterSeminarController extends Controller
{
    public function __construct(private readonly SeminarWorkflow $workflow) {}

    public function create(): View
    {
        return view('seminars.enter-form', ['schools' => $this->schools()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $data = $request->validate([
            'partner_id' => ['required', 'integer', Rule::exists('partners', 'id')->where('type', PartnerType::School->value)],
            'title' => ['required', 'string', 'max:200'],
            'format' => ['nullable', 'string', 'max:120'],
            'participants' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'desired_period' => ['nullable', 'string', 'max:200'],
            'request_note' => ['nullable', 'string', 'max:3000'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'string', 'email', 'max:254'],
        ], ['partner_id.required' => 'Bitte wählen Sie die Schule.', 'partner_id.exists' => 'Bitte wählen Sie eine Schule aus der Liste.', 'title.required' => 'Bitte nennen Sie das Thema oder den Titel.']);

        $partner = Partner::query()->findOrFail((int) $data['partner_id']);

        // Ohne eigene Angabe nehmen wir die Ansprechperson der Schule (Hauptkontakt zuerst, dann die erste mit E-Mail-Adresse).
        if (blank($data['contact_email'] ?? null)) {
            $contact = $partner->contacts()->whereNotNull('email')->where('email', '!=', '')->first();

            if ($contact === null) {
                throw ValidationException::withMessages(['contact_email' => 'Zu dieser Schule ist keine Ansprechperson mit E-Mail-Adresse hinterlegt. Bitte geben Sie eine an.']);
            }

            $data['contact_email'] = $contact->email;
            $data['contact_name'] = filled($data['contact_name'] ?? null) ? $data['contact_name'] : $contact->name;
        } elseif (blank($data['contact_name'] ?? null)) {
            throw ValidationException::withMessages(['contact_name' => 'Bitte nennen Sie die Ansprechperson.']);
        }

        $seminar = $this->workflow->request($partner, $data, $user, true);

        if ($user->can('seminars.review')) {
            return redirect()->route('seminars.office.show', $seminar)->with('success', 'Die Anfrage '.$seminar->number.' ist eingetragen. Die Schule hat keine Bestätigung bekommen.');
        }

        return redirect()->route('seminars.plan.index')->with('success', 'Die Anfrage '.$seminar->number.' ist eingetragen. Die Geschäftsstelle prüft sie; die Schule hat keine Bestätigung bekommen.');
    }

    /** @return array<int, string> */
    private function schools(): array
    {
        return Partner::query()->where('type', PartnerType::School->value)->where('status', '!=', PartnerStatus::Past->value)->orderBy('name')->pluck('name', 'id')->all();
    }
}
