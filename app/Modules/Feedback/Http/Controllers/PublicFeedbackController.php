<?php

namespace App\Modules\Feedback\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Feedback\Services\FeedbackForms;
use App\Modules\Feedback\Services\FeedbackLinks;
use App\Modules\Feedback\Services\FeedbackSubmissions;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Öffentliche Antwortseite eines Feedbacklinks: ohne Konto, ohne Kennung, ohne Speicherung von IP oder Gerät. */
class PublicFeedbackController extends Controller
{
    public function __construct(private readonly FeedbackLinks $links, private readonly FeedbackSubmissions $submissions) {}

    public function show(string $token): View
    {
        $link = $this->links->findByToken($token);

        if ($link === null) {
            return view('feedback.closed', ['reason' => 'unknown']);
        }

        $state = $this->links->state($link);

        if ($state !== 'open') {
            return view('feedback.closed', ['reason' => $state, 'link' => $link]);
        }

        $version = app(FeedbackForms::class)->version($link->form_id, $link->form_version);

        return view('feedback.form', ['link' => $link, 'form' => $version, 'token' => $token, 'questions' => $this->submissions->questions($link)]);
    }

    public function store(Request $request, string $token): RedirectResponse|View
    {
        $link = $this->links->findByToken($token);

        if ($link === null || $this->links->state($link) !== 'open') {
            return view('feedback.closed', ['reason' => $link ? $this->links->state($link) : 'unknown', 'link' => $link]);
        }

        try {
            $this->submissions->submit($link, $request->all());
        } catch (DomainException) {
            return view('feedback.closed', ['reason' => 'full', 'link' => $link]);
        }

        return redirect()->route('feedback.thanks');
    }

    public function thanks(): View
    {
        return view('feedback.thanks');
    }
}
