<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\PrintPlans;
use App\Modules\Seminars\Support\SeminarLead;
use DomainException;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Online-Druckplaner der Seminarmappe: Koordination und Geschäftsstelle (seminars.documents), Seminarleitung und Team des Seminars (ohne Verträge, KVA und Rechnungen). */
class SeminarPrintPlanController extends Controller
{
    public function __construct(private readonly PrintPlans $plans) {}

    public function show(Request $request, Seminar $seminar): View
    {
        $user = $this->authorizeUser($request, $seminar);
        $plan = $this->plans->plan($seminar);
        $summary = $plan !== null ? $this->plans->summary($seminar, (array) $plan->items) : null;

        return view('seminars.print-plan', ['seminar' => $seminar, 'documents' => $this->plans->documents($seminar, $user), 'items' => collect((array) $plan?->items)->keyBy('document_id'), 'summary' => $summary, 'perSheet' => PrintPlans::PER_SHEET, 'staff' => $user->can('seminars.documents')]);
    }

    public function update(Request $request, Seminar $seminar): RedirectResponse
    {
        $user = $this->authorizeUser($request, $seminar);

        try {
            $this->plans->save($seminar, $user, (array) $request->input('items', []));
        } catch (DomainException $exception) {
            return redirect()->route('seminar-print.show', $seminar)->withErrors(['print' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('seminar-print.show', $seminar)->with('success', 'Der Druckplan ist gespeichert.');
    }

    public function list(Request $request, Seminar $seminar): View|Response|RedirectResponse
    {
        $this->authorizeUser($request, $seminar);
        $plan = $this->plans->plan($seminar);
        abort_if($plan === null || (array) $plan->items === [], 404);

        $data = ['seminar' => $seminar->load('partner'), 'summary' => $this->plans->summary($seminar, (array) $plan->items), 'pdf' => false];

        if ($request->query('format') !== 'pdf') {
            return view('seminars.print-list', $data);
        }

        if (! class_exists(Dompdf::class)) {
            return redirect()->route('seminar-print.show', $seminar)->with('error', 'Die PDF-Erzeugung ist auf diesem Server nicht eingerichtet. Nutzen Sie die Druckansicht.');
        }

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('seminars.print-list', ['pdf' => true] + $data)->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return response((string) $pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Druckliste_'.($seminar->number ?: $seminar->id).'.pdf"', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function authorizeUser(Request $request, Seminar $seminar): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $member = $seminar->applications()->where('user_id', $user->id)->where('status', ApplicationStatus::Confirmed->value)->exists();
        abort_unless($user->can('seminars.documents') || SeminarLead::is($user, $seminar) || $member, 403);

        return $user;
    }
}
