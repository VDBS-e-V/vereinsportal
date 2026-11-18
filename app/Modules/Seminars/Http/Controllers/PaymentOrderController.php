<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\PaymentOrder;
use App\Modules\Seminars\Services\TeamContracts;
use App\Support\CsvCell;
use App\Support\Money;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Geschäftsstelle (Buchhaltung): Zahlungsaufträge aus den Verträgen der Teamenden ansehen, exportieren, bezahlen oder ablehnen. */
class PaymentOrderController extends Controller
{
    public function __construct(private readonly TeamContracts $contracts) {}

    public function index(Request $request): View
    {
        $status = array_key_exists((string) $request->query('status', ''), PaymentOrder::STATUS) ? (string) $request->query('status') : null;

        $orders = PaymentOrder::query()->with(['seminar.partner', 'user.person', 'document'])->when($status, fn ($query, string $value) => $query->where('status', $value))->orderByRaw("status = 'open' desc")->orderByDesc('id')->get();

        return view('seminars.payments', ['orders' => $orders, 'status' => $status, 'statusOptions' => PaymentOrder::STATUS, 'openSum' => (int) PaymentOrder::query()->where('status', PaymentOrder::OPEN)->sum('amount_cents')]);
    }

    public function decide(Request $request, PaymentOrder $order): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in([PaymentOrder::PAID, PaymentOrder::REJECTED])], 'note' => ['nullable', 'string', 'max:2000']]);

        try {
            $this->contracts->decide($order, $data['status'], $data['note'] ?? null, $this->user($request));
        } catch (DomainException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return back()->with('success', 'Der Zahlungsauftrag ist als „'.PaymentOrder::STATUS[$data['status']].'“ vermerkt.');
    }

    /** Offene Zahlungsaufträge als CSV (Excel-tauglich) für die Überweisung. Bankdaten stehen nur im Vertrag, nicht in der Liste. */
    public function export(Request $request): StreamedResponse
    {
        $orders = PaymentOrder::query()->where('status', PaymentOrder::OPEN)->with(['seminar', 'user.person'])->orderBy('id')->get();

        return response()->streamDownload(function () use ($orders): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Seminar', 'Titel', 'Person', 'Betrag in Euro', 'Status'], ';', '"', '\\');

            foreach ($orders as $order) {
                fputcsv($out, array_map([CsvCell::class, 'safe'], [$order->seminar->number, $order->seminar->title, $order->user->person?->fullName() ?: (string) $order->user->email, Money::toInput($order->amount_cents), $order->statusLabel()]), ';', '"', '\\');
            }

            fclose($out);
        }, 'zahlungsauftraege-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
