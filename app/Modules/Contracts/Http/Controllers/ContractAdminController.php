<?php

namespace App\Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Contracts\Models\ContractTemplate;
use App\Modules\Contracts\Services\ContractSettings;
use App\Modules\Contracts\Services\ContractTexts;
use App\Support\Html\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Vertragstext (versioniert, nur Vorstand ändert) und Vereinsdaten für den Vertrag. */
class ContractAdminController extends Controller
{
    public function __construct(private readonly ContractTexts $texts, private readonly ContractSettings $settings) {}

    public function index(Request $request): View
    {
        $versions = $this->texts->versions();
        $shown = $request->filled('version') ? $versions->firstWhere('version', (int) $request->query('version')) : $versions->first();

        return view('contracts.admin', [
            'versions' => $versions,
            'shown' => $shown ?? $versions->first(),
            'active' => $versions->first(),
            'settings' => $this->settings->all(),
            'labels' => ContractSettings::LABELS,
            'placeholders' => ContractTexts::PLACEHOLDERS,
            'body' => RichText::forEditor(($shown ?? $versions->first())->body),
            'canTemplate' => $this->user()->can('contracts.template'),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate(['club_name' => ['required', 'string', 'max:150'], 'chair' => ['required', 'string', 'max:150'], 'address' => ['required', 'string', 'max:300'], 'place' => ['required', 'string', 'max:100']]);
        $this->settings->save($data, $this->user());

        return redirect()->route('contracts.admin.index')->with('success', 'Die Vereinsdaten wurden gespeichert.');
    }

    public function storeVersion(Request $request): RedirectResponse
    {
        $request->merge(['body' => RichText::normalize($request->input('body'))]);
        $data = $request->validate(['title' => ['required', 'string', 'max:150'], 'body' => ['required', 'string', 'max:60000']], ['title.required' => 'Bitte geben Sie dem Vertrag einen Titel.', 'body.required' => 'Bitte schreiben Sie den Vertragstext.']);

        $unknown = $this->texts->unknown($data['body']);

        if ($unknown !== []) {
            throw ValidationException::withMessages(['body' => 'Unbekannte Platzhalter '.implode(', ', array_map(fn (string $key): string => '{'.$key.'}', $unknown)).'.']);
        }

        $template = $this->texts->createVersion($data['title'], $data['body'], $this->user());

        return redirect()->route('contracts.admin.index')->with('success', 'Der Vertragstext ist jetzt Version '.$template->version.'. Bestehende Verträge behalten ihre Fassung.');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
