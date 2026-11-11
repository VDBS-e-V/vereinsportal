<?php

namespace App\Modules\Feedback\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Feedback\Models\FeedbackLink;
use App\Modules\Feedback\Services\FeedbackLinks;
use App\Modules\Seminars\Models\Seminar;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Feedbacklinks eines Seminars anlegen, beenden und als Aushang drucken (Berechtigung feedback.manage). */
class FeedbackLinkController extends Controller
{
    public function __construct(private readonly FeedbackLinks $links) {}

    public function store(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate([
            'audiences' => ['required', 'array', 'min:1'],
            'audiences.*' => ['string', Rule::in(['students', 'teachers', 'team'])],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date'],
            'max_responses' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'min_responses' => ['nullable', 'integer', 'min:1', 'max:50'],
            'label' => ['nullable', 'string', 'max:100'],
        ], ['audiences.required' => 'Bitte wählen Sie mindestens eine Gruppe.']);

        try {
            foreach ($data['audiences'] as $audience) {
                $this->links->create($seminar, $audience, $data, $request->user());
            }
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['feedback' => $exception->getMessage()]);
        }

        return redirect()->to(route('seminars.show', $seminar).'#feedback-heading')->with('status', count($data['audiences']) === 1 ? 'Der Feedbacklink wurde angelegt.' : 'Die Feedbacklinks wurden angelegt.');
    }

    public function close(Request $request, Seminar $seminar): RedirectResponse
    {
        $ids = array_map('intval', (array) $request->input('ids', []));

        if ($ids === []) {
            return back()->withErrors(['feedback' => 'Bitte wählen Sie mindestens einen Link aus.']);
        }

        $count = 0;

        foreach (FeedbackLink::query()->where('seminar_id', $seminar->id)->whereIn('id', $ids)->get() as $link) {
            $this->links->close($link, $request->user());
            $count++;
        }

        return redirect()->to(route('seminars.show', $seminar).'#feedback-heading')->with('status', $count === 1 ? 'Der Link wurde beendet.' : $count.' Links wurden beendet.');
    }

    public function print(Seminar $seminar, FeedbackLink $link): View
    {
        abort_unless($link->seminar_id === $seminar->id, 404);

        return view('feedback.print', ['seminar' => $seminar, 'link' => $link]);
    }
}
