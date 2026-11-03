<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\MailSnippet;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Support\Html\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Textbausteine für Schreiben (Geschäftsstelle, Vorstand): kurze wiederverwendbare Abschnitte, die man beim Schreiben einfügt. */
class MailSnippetController extends Controller
{
    public function index(Request $request): View
    {
        $edit = $request->filled('bearbeiten') ? MailSnippet::query()->find((int) $request->query('bearbeiten')) : null;

        return view('mailings.snippets', [
            'snippets' => MailSnippet::query()->orderBy('name')->get(),
            'edit' => $edit,
            'body' => RichText::forEditor($edit?->body),
            'placeholders' => MailPlaceholders::definitions('general'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['body' => RichText::normalize($request->input('body'))]);
        $id = $request->filled('snippet_id') ? (int) $request->input('snippet_id') : null;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('mail_snippets', 'name')->ignore($id)],
            'body' => ['required', 'string', 'max:5000'],
        ], ['name.required' => 'Bitte geben Sie dem Baustein einen Namen.', 'name.unique' => 'Es gibt schon einen Baustein mit diesem Namen.', 'body.required' => 'Bitte schreiben Sie den Text.', 'body.max' => 'Der Text ist zu lang.']);

        $unknown = MailPlaceholders::unknown($data['body'], 'general');

        if ($unknown !== []) {
            throw ValidationException::withMessages(['body' => 'Unbekannte Platzhalter '.implode(', ', array_map(fn (string $key): string => '{'.$key.'}', $unknown)).'.']);
        }

        $snippet = $id !== null ? MailSnippet::query()->findOrFail($id) : new MailSnippet;
        $snippet->forceFill(['name' => trim($data['name']), 'body' => $data['body'], 'updated_by_user_id' => $this->user()->id])->save();

        return redirect()->route('mailings.templates.snippets')->with('success', 'Der Baustein „'.$snippet->name.'“ wurde gespeichert.');
    }

    public function destroy(MailSnippet $snippet): RedirectResponse
    {
        $snippet->delete();

        return redirect()->route('mailings.templates.snippets')->with('success', 'Der Baustein wurde gelöscht.');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
