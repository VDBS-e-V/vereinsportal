<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\PrintPlans;
use App\Modules\Seminars\Services\SeminarFolder;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/** Seminarmappe (ZIP) und Online-Druckplaner mit Druck-PDF. Zugang: Büro, Koordination, Seminarleitung und zugesagtes Team. */
class SeminarFilesController extends Controller
{
    public function __construct(private readonly PrintPlans $plans, private readonly SeminarFolder $folder) {}

    public function planner(Request $request, Seminar $seminar): View
    {
        $this->authorizeUse($request, $seminar);

        $items = collect($seminar->printPlan?->items ?? [])->keyBy('document_id');
        $documents = $this->plans->printable($seminar);

        return view('seminars.print-plan', [
            'seminar' => $seminar,
            'documents' => $documents,
            'unusable' => $this->plans->unusable($seminar),
            'items' => $items,
            'perSheet' => PrintPlans::PER_SHEET,
            'totals' => $items->isEmpty() ? null : $this->plans->totals($seminar, $items->values()->all()),
        ]);
    }

    public function save(Request $request, Seminar $seminar): RedirectResponse
    {
        $user = $this->authorizeUse($request, $seminar);
        $data = $request->validate(['rows' => ['nullable', 'array']]);

        try {
            $this->plans->save($seminar, $this->plans->normalize($seminar, (array) ($data['rows'] ?? [])), $user);
        } catch (DomainException $exception) {
            return back()->withErrors(['plan' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('seminars.files.planner', $seminar)->with('success', 'Der Druckplan ist gespeichert.');
    }

    /** Die Druck-PDF aus dem gespeicherten Plan. */
    public function pdf(Request $request, Seminar $seminar): Response|RedirectResponse
    {
        $this->authorizeUse($request, $seminar);
        $plan = $seminar->printPlan;

        if ($plan === null || $plan->items === []) {
            return redirect()->route('seminars.files.planner', $seminar)->withErrors(['plan' => 'Speichern Sie zuerst einen Druckplan.']);
        }

        try {
            $content = $this->plans->render($seminar, $plan->items);
        } catch (DomainException $exception) {
            return redirect()->route('seminars.files.planner', $seminar)->withErrors(['plan' => $exception->getMessage()]);
        }

        return response($content, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Druckauftrag-'.$seminar->number.'.pdf"']);
    }

    public function folder(Request $request, Seminar $seminar): BinaryFileResponse|RedirectResponse
    {
        $this->authorizeUse($request, $seminar);

        try {
            $path = $this->folder->build($seminar);
        } catch (DomainException $exception) {
            return back()->withErrors(['plan' => $exception->getMessage()]);
        }

        return response()->download($path, $this->folder->filename($seminar))->deleteFileAfterSend(true);
    }

    private function authorizeUse(Request $request, Seminar $seminar): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $this->plans->canUse($user, $seminar), 404);

        return $user;
    }
}
