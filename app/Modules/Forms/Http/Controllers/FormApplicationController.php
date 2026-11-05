<?php

namespace App\Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Forms\Models\CostCenter;
use App\Modules\Forms\Models\FormApplication;
use App\Modules\Forms\Models\FormFile;
use App\Modules\Forms\Services\FormApplications;
use App\Modules\Forms\Services\Iban;
use App\Modules\Forms\Support\FormTypes;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Support\Money;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Eigene Anträge: Liste, neuer Antrag, Entwurf bearbeiten, einreichen, ansehen (Berechtigung forms.submit). */
class FormApplicationController extends Controller
{
    public function __construct(private readonly FormApplications $applications) {}

    public function index(Request $request): View
    {
        return view('forms.index', [
            'applications' => FormApplication::query()->where('applicant_user_id', $request->user()->id)->orderByDesc('id')->get(),
            'types' => collect(FormTypes::enabled())->mapWithKeys(fn (string $type): array => [$type => FormTypes::TYPES[$type]])->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = (string) $request->input('type', 'expense');

        try {
            $application = $this->applications->create($request->user(), $type);
        } catch (DomainException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()]);
        }

        return redirect()->route('forms.edit', $application);
    }

    public function edit(Request $request, FormApplication $application): View|RedirectResponse
    {
        $this->own($request, $application);

        if (! $application->isEditable()) {
            return redirect()->route('forms.show', $application);
        }

        $application->load(['items.files']);
        $rows = max(3, $application->items->count() + 1, (int) $request->query('rows', 0));

        return view('forms.edit', [
            'application' => $application,
            'rows' => min($rows, 30),
            'seminars' => Seminar::query()->orderByDesc('id')->limit(300)->get()->mapWithKeys(fn (Seminar $seminar): array => [$seminar->id => trim(($seminar->number ? $seminar->number.' ' : '').$seminar->title)])->all(),
            'costCenters' => CostCenter::query()->where('active', true)->orderBy('code')->get()->mapWithKeys(fn (CostCenter $center): array => [$center->code => $center->label()])->all(),
            'iban' => $application->iban_encrypted ? Iban::pretty((string) $application->iban_encrypted) : '',
            'items' => $application->items->values(),
            'settings' => app(\App\Modules\Forms\Services\FormSettings::class),
            'warnings' => $this->applications->warnings($application),
            'problems' => $application->status === 'returned' || $request->boolean('check') ? $this->applications->problems($application) : [],
        ]);
    }

    public function update(Request $request, FormApplication $application): RedirectResponse
    {
        $this->own($request, $application);
        $action = (string) $request->input('action', 'save');

        if ($action === 'discard') {
            try {
                $this->applications->discard($application, $request->user());
            } catch (DomainException $exception) {
                return back()->withErrors(['form' => $exception->getMessage()]);
            }

            return redirect()->route('forms.index')->with('status', 'Der Entwurf wurde gelöscht.');
        }

        $count = count((array) $request->input('items', []));

        try {
            $this->applications->saveDraft($application, $request->all(), (array) $request->file('files', []));

            if ($action === 'submit') {
                $this->applications->submit($application->refresh(), $request->user());

                return redirect()->route('forms.show', $application)->with('status', 'Ihr Antrag wurde eingereicht. Sie hören von der Geschäftsstelle.');
            }
        } catch (DomainException $exception) {
            return redirect()->route('forms.edit', ['application' => $application, 'rows' => $count, 'check' => 1])->withInput($request->except('files'))->withErrors(['form' => $exception->getMessage()]);
        }

        return redirect()->route('forms.edit', ['application' => $application, 'rows' => $action === 'add_row' ? $count + 1 : $count])->with('status', $action === 'add_row' ? null : 'Der Entwurf wurde gespeichert.');
    }

    public function show(Request $request, FormApplication $application): View
    {
        $this->readable($request, $application);
        $application->load(['items.files', 'events', 'seminar']);

        return view('forms.show', ['application' => $application, 'office' => false, 'iban' => null]);
    }

    public function file(Request $request, FormApplication $application, FormFile $file): StreamedResponse
    {
        $this->readable($request, $application);
        abort_unless($file->request_id === $application->id && Storage::disk(FormFile::DISK)->exists($file->path), 404);

        return Storage::disk(FormFile::DISK)->download($file->path, $file->original_name, ['Content-Type' => $file->mime]);
    }

    private function own(Request $request, FormApplication $application): void
    {
        abort_unless($application->applicant_user_id === $request->user()->id, 404);
    }

    private function readable(Request $request, FormApplication $application): void
    {
        $user = $request->user();
        abort_unless($application->applicant_user_id === $user->id || Capabilities::has($user, Capability::FormsView), 404);
    }
}
