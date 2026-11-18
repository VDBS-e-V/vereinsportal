<?php

namespace App\Modules\Quotes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\Quotes\Models\SeminarQuote;
use App\Modules\Quotes\Services\Quotes;
use App\Modules\Seminars\Models\Seminar;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Bereich Schulen: den Kostenvoranschlag annehmen, ablehnen oder nachfragen. */
class SchoolQuoteController extends Controller
{
    public function __construct(private readonly Quotes $quotes, private readonly SchoolAccounts $accounts) {}

    public function accept(Request $request, Seminar $seminar, SeminarQuote $quote): RedirectResponse
    {
        return $this->answer($request, $seminar, $quote, fn (User $user) => $this->quotes->accept($quote, $user), 'Danke. Sie haben den Kostenvoranschlag angenommen. Die Geschäftsstelle meldet sich zum Vertrag.');
    }

    public function decline(Request $request, Seminar $seminar, SeminarQuote $quote): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        return $this->answer($request, $seminar, $quote, fn (User $user) => $this->quotes->decline($quote, $user, $data['note'] ?? null), 'Sie haben den Kostenvoranschlag abgelehnt.');
    }

    public function question(Request $request, Seminar $seminar, SeminarQuote $quote): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']], ['note.required' => 'Bitte schreiben Sie Ihre Frage oder Ihren Wunsch.']);

        return $this->answer($request, $seminar, $quote, fn (User $user) => $this->quotes->question($quote, $user, $data['note']), 'Ihre Nachricht ist bei der Geschäftsstelle angekommen.');
    }

    private function answer(Request $request, Seminar $seminar, SeminarQuote $quote, callable $action, string $success): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $account = $this->accounts->forUser($user);
        abort_unless($account !== null && $seminar->partner_id === $account->partner_id && $quote->seminar_id === $seminar->id, 404);

        try {
            $action($user);
        } catch (DomainException $exception) {
            return back()->withErrors(['quote' => $exception->getMessage()]);
        }

        return redirect()->route('school.seminars.show', $seminar)->with('success', $success);
    }
}
