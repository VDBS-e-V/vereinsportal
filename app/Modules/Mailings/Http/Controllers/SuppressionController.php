<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\MailSuppression;
use App\Modules\Mailings\Models\MailUnsubscribe;
use App\Modules\Mailings\Services\MailSuppressions;
use App\Modules\Mailings\Support\MailGroups;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** Gesperrte Adressen (Vorstand und Geschäftsstelle): ansehen, manuell sperren, freigeben. Die Zahlen der Abmeldungen je Thema stehen dabei. */
class SuppressionController extends Controller
{
    public function __construct(private readonly MailSuppressions $suppressions) {}

    public function index(Request $request): View
    {
        $base = Str::beforeLast((string) $request->route()?->getName(), '.');

        return view('mailings.suppressions', [
            'base' => $base,
            'area' => $base === 'mailings.board' ? 'Vorstand' : 'Geschäftsstelle',
            'suppressions' => MailSuppression::query()->active()->orderByDesc('id')->paginate(25),
            'unsubscribeCounts' => MailUnsubscribe::query()->selectRaw('topic, count(*) as total')->groupBy('topic')->pluck('total', 'topic')->all(),
            'topics' => MailGroups::TOPICS,
            'reasons' => MailSuppression::REASONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190'], 'note' => ['nullable', 'string', 'max:250']], ['email.required' => 'Bitte geben Sie eine E-Mail-Adresse ein.', 'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.']);

        $this->suppressions->suppress($data['email'], MailSuppression::MANUAL, $data['note'] ?? null, $this->user());

        return redirect()->route($this->base($request).'.suppressions')->with('success', 'Die Adresse ist gesperrt. Schreiben an Gruppen gehen nicht mehr dorthin.');
    }

    public function release(Request $request, MailSuppression $suppression): RedirectResponse
    {
        $this->suppressions->release($suppression, $this->user());

        return redirect()->route($this->base($request).'.suppressions')->with('success', 'Die Sperre wurde aufgehoben.');
    }

    private function base(Request $request): string
    {
        return Str::beforeLast((string) $request->route()?->getName(), '.');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
