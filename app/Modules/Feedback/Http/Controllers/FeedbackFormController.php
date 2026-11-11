<?php

namespace App\Modules\Feedback\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Feedback\Models\FeedbackForm;
use App\Modules\Feedback\Services\FeedbackForms;
use App\Modules\Feedback\Support\FeedbackDefaults;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Feedbackformulare bearbeiten (Berechtigung feedback.manage): Fragen, Reihenfolge, Texte; jede Änderung ist eine neue Fassung. */
class FeedbackFormController extends Controller
{
    public function __construct(private readonly FeedbackForms $forms) {}

    public function index(): View
    {
        return view('feedback.forms-index', ['forms' => $this->forms->all()]);
    }

    public function edit(FeedbackForm $form): View
    {
        return view('feedback.forms-edit', ['form' => $form, 'types' => FeedbackDefaults::TYPES]);
    }

    public function preview(FeedbackForm $form): View
    {
        return view('feedback.form', ['link' => null, 'form' => $form, 'token' => null, 'questions' => $form->questions, 'preview' => true]);
    }

    public function update(Request $request, FeedbackForm $form): RedirectResponse
    {
        try {
            $this->forms->save($form, $request->all(), $request->user());
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['form' => $exception->getMessage()]);
        }

        return redirect()->route('feedback-forms.edit', $form)->with('status', 'Das Formular wurde gespeichert. Es gilt als neue Fassung für Links, die ab jetzt angelegt werden.');
    }

    public function reset(Request $request, FeedbackForm $form): RedirectResponse
    {
        try {
            $this->forms->reset($form, $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()]);
        }

        return redirect()->route('feedback-forms.edit', $form)->with('status', 'Das Formular wurde auf den Standard zurückgesetzt.');
    }
}
