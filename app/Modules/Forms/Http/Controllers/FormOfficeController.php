<?php

namespace App\Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Forms\Models\CostCenter;
use App\Modules\Forms\Models\FormApplication;
use App\Modules\Forms\Services\ApprovalPolicy;
use App\Modules\Forms\Services\FormApplications;
use App\Modules\Forms\Services\FormSettings;
use App\Modules\Forms\Services\Iban;
use App\Modules\Forms\Support\FormTypes;
use App\Support\CsvCell;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Anträge in der Geschäftsstelle: Eingang, Prüfung, Freigabe, Auszahlungsvermerk, Sammelliste, Kostenstellen, Einstellungen. */
class FormOfficeController extends Controller
{
    public function __construct(private readonly FormApplications $applications, private readonly ApprovalPolicy $policy, private readonly FormSettings $settings) {}

    public function index(Request $request): View
    {
        $statuses = array_values(array_intersect(array_map('strval', (array) $request->query('status', [])), array_keys(FormApplication::STATUS_LABELS)));
        $types = array_values(array_intersect(array_map('strval', (array) $request->query('art', [])), array_keys(FormTypes::TYPES)));
        $q = trim((string) $request->query('q', ''));

        $query = FormApplication::query()->where('status', '!=', 'draft')->orderByDesc('submitted_at')->orderByDesc('id');

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        if ($types !== []) {
            $query->whereIn('type', $types);
        }

        if ($q !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
            $query->where(fn ($inner) => $inner->where('number', 'like', $like)->orWhere('applicant_name', 'like', $like)->orWhere('purpose', 'like', $like));
        }

        $open = FormApplication::query()->whereIn('status', ['submitted', 'in_review'])->count();

        return view('forms.office.index', [
            'applications' => $query->paginate(25)->withQueryString(),
            'filters' => ['status' => $statuses, 'art' => $types, 'q' => $q],
            'statusOptions' => collect(FormApplication::STATUS_LABELS)->except('draft')->all(),
            'typeOptions' => collect(FormTypes::TYPES)->map(fn (array $type): string => $type['label'])->all(),
            'open' => $open,
            'canManage' => Capabilities::has($request->user(), Capability::FormsManage),
        ]);
    }

    public function show(Request $request, FormApplication $application): View
    {
        abort_if($application->status === 'draft', 404);
        $application->load(['items.files', 'events', 'seminar', 'checkedBy.person', 'approvedBy.person']);
        $user = $request->user();

        return view('forms.show', [
            'application' => $application,
            'office' => true,
            'iban' => Capabilities::has($user, Capability::FormsManage) && $application->iban_encrypted ? Iban::pretty((string) $application->iban_encrypted) : null,
            'can' => [
                'check' => $this->policy->canCheck($user, $application) && in_array($application->status, ['submitted', 'in_review'], true),
                'approve' => $this->policy->canApprove($user, $application) && in_array($application->status, ['submitted', 'in_review'], true),
                'pay' => Capabilities::has($user, Capability::FormsManage) && $application->status === 'approved' && $application->type !== 'purchase',
                'purchase' => Capabilities::has($user, Capability::FormsManage) && $application->type === 'purchase' && in_array($application->status, ['approved', 'ordered', 'delivered'], true),
                'exception' => Capabilities::has($user, Capability::FormsManage) && in_array($application->status, ['returned', 'submitted', 'in_review'], true),
            ],
            'limit' => $this->settings->approvalLimit(),
        ]);
    }

    public function act(Request $request, FormApplication $application): RedirectResponse
    {
        $user = $request->user();
        $action = (string) $request->input('action');
        $note = (string) $request->input('note', '');

        try {
            match ($action) {
                'review' => $this->applications->startReview($application, $user),
                'check' => $this->applications->check($application, $user),
                'approve' => $this->applications->approve($application, $user),
                'return' => $this->applications->returnToApplicant($application, $user, $note),
                'reject' => $this->applications->reject($application, $user, $note),
                'paid' => $this->applications->markPaid($application, $user, $note),
                'ordered' => $this->applications->markOrdered($application, $user),
                'delivered' => $this->applications->markDelivered($application, $user),
                'close' => $this->applications->close($application, $user),
                'exception' => $this->applications->grantException($application, $user, $note),
                default => throw new DomainException('Diese Aktion gibt es nicht.'),
            };
        } catch (DomainException $exception) {
            return redirect()->route('forms-office.show', $application)->with('warning', $exception->getMessage());
        }

        return redirect()->route('forms-office.show', $application)->with('status', 'Gespeichert.');
    }

    /** Sammelliste der ausgewählten freigegebenen Anträge (CSV mit IBAN), nur für die Geschäftsstelle. */
    public function exportPayout(Request $request): StreamedResponse|RedirectResponse
    {
        $ids = array_map('intval', (array) $request->input('ids', []));
        $applications = FormApplication::query()->whereIn('id', $ids)->where('status', 'approved')->whereIn('type', ['expense', 'travel'])->with(['seminar', 'checkedBy.person', 'approvedBy.person'])->orderBy('number')->get();

        if ($applications->isEmpty()) {
            return back()->with('warning', 'Bitte wählen Sie mindestens einen freigegebenen Antrag aus.');
        }

        foreach ($applications as $application) {
            $application->forceFill(['exported_at' => now()])->save();
            $this->applications->event($application, 'exported', $request->user());
        }

        AuditLog::record(AuditEvent::FormExported, $request->user(), null, ['anzahl' => $applications->count()]);

        return response()->streamDownload(function () use ($applications): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Antragsnummer', 'Art', 'Eingereicht am', 'Freigegeben am', 'Antragsteller:in', 'E-Mail', 'Kontoinhaber:in', 'IBAN', 'Betrag gesamt (EUR)', 'Verwendungszweck', 'Kostenstelle', 'Seminar-Nr.', 'Geprüft von', 'Freigegeben von', 'Ausnahme Belegfrist'], ';', '"', '\\');

            foreach ($applications as $application) {
                fputcsv($out, array_map([CsvCell::class, 'safe'], [
                    $application->number, $application->typeLabel(), $application->submitted_at?->format('d.m.Y'), $application->approved_at?->format('d.m.Y'),
                    $application->applicant_name, (string) $application->applicant_email, (string) $application->account_holder, Iban::pretty((string) $application->iban_encrypted),
                    number_format($application->total_cents / 100, 2, ',', ''), Str::limit($application->number.' '.$application->typeLabel().($application->seminar?->number ? ' Seminar '.$application->seminar->number : ''), 140, ''),
                    (string) $application->cost_center, (string) ($application->seminar?->number ?? ''),
                    (string) ($application->checkedBy?->person?->fullName() ?? ''), (string) ($application->approvedBy?->person?->fullName() ?? ''), $application->exception_by_user_id ? 'ja' : 'nein',
                ]), ';', '"', '\\');
            }

            fclose($out);
        }, 'auszahlungsliste-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function invoice(Request $request, FormApplication $application): RedirectResponse
    {
        $request->validate(['invoice' => ['required', 'file']], ['invoice.required' => 'Bitte wählen Sie die Rechnung aus.']);

        try {
            $this->applications->attachInvoice($application, $request->user(), $request->file('invoice'));
        } catch (DomainException $exception) {
            return redirect()->route('forms-office.show', $application)->with('warning', $exception->getMessage());
        }

        return redirect()->route('forms-office.show', $application)->with('status', 'Die Rechnung wurde angehängt.');
    }

    /** Buchungsliste: eine Zeile je Position, filterbar nach Zeitraum, Art und Stand. */
    public function exportBookings(Request $request): StreamedResponse
    {
        $applications = $this->filteredForExport($request)->with(['items.files', 'seminar'])->orderBy('number')->get();

        return response()->streamDownload(function () use ($applications): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Antragsnummer', 'Position', 'Belegdatum', 'Art', 'Beschreibung', 'Menge', 'Einzelbetrag (EUR)', 'Betrag (EUR)', 'Kilometer', 'Start', 'Ziel', 'Verkehrsmittel', 'Lieferant', 'Kostenstelle', 'Seminar-Nr.', 'Seminar-Titel', 'Antragsteller:in', 'Freigabe-Datum', 'Auszahlungsdatum', 'Auszahlungsreferenz', 'Beleg'], ';', '"', '\\');

            foreach ($applications as $application) {
                foreach ($application->items as $item) {
                    $extra = (array) $item->extra;
                    fputcsv($out, array_map([CsvCell::class, 'safe'], [
                        $application->number, $item->position, $item->date->format('d.m.Y'), $item->kindLabel(), $item->description, rtrim(rtrim(number_format($item->quantity, 2, ',', ''), '0'), ','),
                        number_format($item->unit_cents / 100, 2, ',', ''), number_format($item->amount_cents / 100, 2, ',', ''), isset($extra['km']) ? str_replace('.', ',', (string) $extra['km']) : '', (string) ($extra['from'] ?? ''), (string) ($extra['to'] ?? ''),
                        (string) ($extra['mode'] ?? ''), (string) ($extra['supplier'] ?? ''), (string) $application->cost_center, (string) ($application->seminar?->number ?? ''), (string) ($application->seminar?->title ?? ''),
                        $application->applicant_name, $application->approved_at?->format('d.m.Y'), $application->paid_at?->format('d.m.Y'), (string) $application->paid_reference, $item->files->pluck('original_name')->implode(', '),
                    ]), ';', '"', '\\');
                }
            }

            fclose($out);
        }, 'buchungsliste-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Jahresübersicht: Summen je Kostenstelle und Art. */
    public function exportSummary(Request $request): StreamedResponse
    {
        $rows = $this->filteredForExport($request)->get()->groupBy(fn (FormApplication $application): string => ($application->cost_center ?: '(ohne)').'|'.$application->type)->map(fn ($group, string $key): array => [explode('|', $key)[0], FormTypes::label(explode('|', $key)[1]), $group->count(), $group->sum('total_cents')])->sortKeys()->values();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Kostenstelle', 'Art', 'Anträge', 'Summe (EUR)'], ';', '"', '\\');

            foreach ($rows as [$center, $type, $count, $sum]) {
                fputcsv($out, array_map([CsvCell::class, 'safe'], [$center, $type, $count, number_format($sum / 100, 2, ',', '')]), ';', '"', '\\');
            }

            fputcsv($out, ['Summe', '', $rows->sum(2), number_format($rows->sum(3) / 100, 2, ',', '')], ';', '"', '\\');
            fclose($out);
        }, 'jahresuebersicht-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return \Illuminate\Database\Eloquent\Builder<FormApplication> */
    private function filteredForExport(Request $request)
    {
        $statuses = array_values(array_intersect(array_map('strval', (array) $request->query('status', ['approved', 'paid', 'ordered', 'delivered', 'closed'])), array_keys(FormApplication::STATUS_LABELS)));
        $types = array_values(array_intersect(array_map('strval', (array) $request->query('art', [])), array_keys(FormTypes::TYPES)));
        $query = FormApplication::query()->where('status', '!=', 'draft')->whereIn('status', $statuses ?: ['approved']);

        if ($types !== []) {
            $query->whereIn('type', $types);
        }

        foreach (['von' => '>=', 'bis' => '<='] as $key => $operator) {
            if (filled($request->query($key))) {
                try {
                    $query->whereDate('approved_at', $operator, \Illuminate\Support\Carbon::parse((string) $request->query($key))->toDateString());
                } catch (\Throwable) {
                }
            }
        }

        return $query;
    }

    public function costCenters(): View
    {
        return view('forms.office.cost-centers', ['centers' => CostCenter::query()->orderBy('code')->get()]);
    }

    public function storeCostCenter(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9._\-]+$/', 'unique:cost_centers,code'], 'name' => ['required', 'string', 'max:150']], ['code.required' => 'Bitte geben Sie eine Kennung an.', 'code.unique' => 'Diese Kennung gibt es schon.', 'code.regex' => 'Die Kennung darf nur Buchstaben, Ziffern, Punkt, Bindestrich und Unterstrich enthalten.', 'name.required' => 'Bitte geben Sie einen Namen an.']);
        CostCenter::query()->create($data + ['active' => true]);

        return redirect()->route('forms-office.cost-centers')->with('status', 'Die Kostenstelle wurde angelegt.');
    }

    public function toggleCostCenter(CostCenter $center): RedirectResponse
    {
        $center->forceFill(['active' => ! $center->active])->save();

        return redirect()->route('forms-office.cost-centers')->with('status', $center->active ? 'Die Kostenstelle ist wieder aktiv.' : 'Die Kostenstelle ist beendet und steht nicht mehr zur Auswahl.');
    }

    public function settings(): View
    {
        return view('forms.office.settings', ['values' => collect(FormSettings::DEFAULTS)->mapWithKeys(fn (int $default, string $key): array => [$key => $this->settings->int($key)])->all()]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate(['approval_limit' => ['required', 'string'], 'km_rate' => ['required', 'string'], 'max_km_per_trip' => ['required', 'integer', 'min:1', 'max:100000']], ['approval_limit.required' => 'Bitte geben Sie die Grenze an.', 'km_rate.required' => 'Bitte geben Sie den Kilometersatz an.']);
        $limit = \App\Modules\Seminars\Support\Money::parseEuro($data['approval_limit']);
        $rate = \App\Modules\Seminars\Support\Money::parseEuro($data['km_rate']);

        if ($limit === null || $rate === null) {
            return back()->withInput()->withErrors(['approval_limit' => 'Bitte geben Sie Beträge in Euro an, zum Beispiel 50,00 und 0,30.']);
        }

        $this->settings->save(['approval_limit_cents' => $limit, 'km_rate_cents' => $rate, 'max_km_per_trip' => (int) $data['max_km_per_trip']], $request->user());

        return redirect()->route('forms-office.settings')->with('status', 'Die Einstellungen wurden gespeichert.');
    }
}
