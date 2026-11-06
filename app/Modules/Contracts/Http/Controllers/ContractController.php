<?php

namespace App\Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Contracts\Models\SeminarContract;
use App\Modules\Contracts\Services\ContractSettings;
use App\Modules\Contracts\Services\Contracts;
use App\Modules\Contracts\Services\ContractTexts;
use App\Modules\Seminars\Models\Seminar;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** Verträge eines Seminars (Koordination, Geschäftsstelle, Vorstand): erzeugen, bearbeiten, drucken, Scan ablegen, stornieren. Daten nie in Listen, nie im Protokoll. */
class ContractController extends Controller
{
    public function __construct(private readonly Contracts $contracts, private readonly ContractTexts $texts, private readonly ContractSettings $settings) {}

    public function store(Request $request, Seminar $seminar): RedirectResponse
    {
        $ids = $request->boolean('all') ? null : array_values(array_filter(array_map('intval', (array) $request->input('applications', []))));

        if ($ids === []) {
            return back()->with('error', 'Bitte wählen Sie mindestens eine Person oder „Alle Verträge erzeugen“.');
        }

        $count = $this->contracts->createMany($seminar, $ids, $this->user());

        return redirect()->to(route('seminars.show', $seminar).'#contracts-heading')->with($count > 0 ? 'success' : 'error', $count > 0 ? $count.' Vertrag/Verträge erzeugt.' : 'Es gab keine Person ohne Vertrag.');
    }

    public function edit(Seminar $seminar, SeminarContract $contract): View
    {
        $this->belongs($seminar, $contract);

        return view('contracts.edit', ['seminar' => $seminar, 'contract' => $contract, 'data' => $contract->data]);
    }

    public function update(Request $request, Seminar $seminar, SeminarContract $contract): RedirectResponse
    {
        $this->belongs($seminar, $contract);

        $request->validate([
            'name' => ['nullable', 'string', 'max:190'], 'address' => ['nullable', 'string', 'max:300'], 'tax_number' => ['nullable', 'string', 'max:30'],
            'vat_mode' => ['nullable', 'in:19,7,none'], 'bank_name' => ['nullable', 'string', 'max:120'], 'bic' => ['nullable', 'string', 'max:20'], 'iban' => ['nullable', 'string', 'max:40'],
            'service_text' => ['nullable', 'string', 'max:2000'], 'amount' => ['nullable', 'string', 'max:20'], 'amount_note' => ['nullable', 'string', 'max:300'],
            'place' => ['nullable', 'string', 'max:100'], 'date' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $this->contracts->update($contract, $request->all(), $this->user());
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('contracts.edit', [$seminar, $contract])->with('success', 'Der Vertrag wurde gespeichert.');
    }

    /** Druckansicht eines Vertrags. */
    public function show(Seminar $seminar, SeminarContract $contract): View
    {
        $this->belongs($seminar, $contract);

        return $this->printView($seminar, collect([$contract]));
    }

    /** Sammel-Druckansicht: eine Seite je Vertrag (alle gültigen oder die gewählten). */
    public function printAll(Request $request, Seminar $seminar): View
    {
        $ids = array_values(array_filter(array_map('intval', (array) $request->query('ids', []))));
        $contracts = SeminarContract::query()->where('seminar_id', $seminar->id)->where('status', '!=', 'cancelled')->when($ids !== [], fn ($query) => $query->whereIn('id', $ids))->orderBy('id')->get();

        abort_if($contracts->isEmpty(), 404);

        return $this->printView($seminar, $contracts);
    }

    public function scan(Request $request, Seminar $seminar, SeminarContract $contract): RedirectResponse
    {
        $this->belongs($seminar, $contract);
        $request->validate(['scan' => ['required', 'file', 'max:10240']], ['scan.required' => 'Bitte wählen Sie den unterschriebenen Scan.']);

        try {
            $this->contracts->attachScan($contract, $request->file('scan'), $this->user());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->to(route('seminars.show', $seminar).'#contracts-heading')->with('success', 'Der unterschriebene Vertrag wurde abgelegt.');
    }

    public function cancel(Seminar $seminar, SeminarContract $contract): RedirectResponse
    {
        $this->belongs($seminar, $contract);

        try {
            $this->contracts->cancel($contract);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->to(route('seminars.show', $seminar).'#contracts-heading')->with('success', 'Der Vertrag wurde storniert.');
    }

    /** @param  \Illuminate\Support\Collection<int, SeminarContract>  $contracts */
    private function printView(Seminar $seminar, $contracts): View
    {
        $club = $this->settings->all();
        $pages = $contracts->map(function (SeminarContract $contract) use ($club): array {
            $template = $contract->template ?? $this->texts->active();

            return ['contract' => $contract, 'title' => $template->title, 'html' => $this->texts->render($template, (array) $contract->data, $club), 'club' => $club];
        });

        return view('contracts.print', ['seminar' => $seminar, 'pages' => $pages, 'club' => $club]);
    }

    private function belongs(Seminar $seminar, SeminarContract $contract): void
    {
        abort_unless($contract->seminar_id === $seminar->id, 404);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
