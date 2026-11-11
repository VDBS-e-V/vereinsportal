<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Mailings\Services\MailUnsubscribes;
use App\Modules\Mailings\Support\MailGroups;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Abmelden von Mitteilungen ohne Anmeldung. Der Link aus der Mail enthält Adresse und Thema verschlüsselt. Die Seite zeigt, was abbestellt wird, und lässt
 * es mit einem Klick tun und wieder rückgängig machen. Der POST dient auch dem One-Click-Verfahren der Postfachanbieter (RFC 8058: Body „List-Unsubscribe=One-Click“).
 */
class UnsubscribeController extends Controller
{
    public function __construct(private readonly MailUnsubscribes $unsubscribes) {}

    public function show(string $token): View
    {
        $data = $this->resolve($token);

        return view('mailings.unsubscribe', [
            'token' => $token,
            'masked' => MailUnsubscribes::mask($data['email']),
            'topicLabel' => MailGroups::TOPICS[$data['topic']],
            'unsubscribed' => $this->unsubscribes->isUnsubscribed($data['email'], $data['topic']),
        ]);
    }

    public function store(Request $request, string $token): Response|RedirectResponse
    {
        $data = $this->resolve($token);
        $this->unsubscribes->unsubscribe($data['email'], $data['topic']);

        // One-Click des Postfachanbieters: kurze Bestätigung statt Weiterleitung
        if ($request->input('List-Unsubscribe') === 'One-Click') {
            return response('Abgemeldet', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return redirect()->route('mailings.unsubscribe.show', $token)->with('success', 'Sie bekommen diese Mitteilungen nicht mehr.');
    }

    public function resubscribe(string $token): RedirectResponse
    {
        $data = $this->resolve($token);
        $this->unsubscribes->resubscribe($data['email'], $data['topic']);

        return redirect()->route('mailings.unsubscribe.show', $token)->with('success', 'Sie bekommen diese Mitteilungen wieder.');
    }

    /** @return array{email: string, topic: string} */
    private function resolve(string $token): array
    {
        $data = $this->unsubscribes->parse($token);

        abort_if($data === null || ! array_key_exists($data['topic'], MailGroups::TOPICS), 404);

        return $data;
    }
}
