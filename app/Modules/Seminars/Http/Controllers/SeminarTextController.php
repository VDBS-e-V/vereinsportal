<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Services\Quotes;
use App\Modules\Seminars\Support\QuoteTexts;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Globale Texte der Produkte (Titel und Standardbeschreibung mit Platzhaltern) für den gedruckten Kostenvoranschlag. */
class SeminarTextController extends Controller
{
    public function __construct(private readonly Quotes $quotes) {}

    public function index(): View
    {
        $examples = [];

        foreach (SeminarType::cases() as $type) {
            $examples[$type->value] = QuoteTexts::defaults($type, \App\Modules\Seminars\Support\QuoteCalculator::defaults($type));
        }

        return view('seminars.texts', [
            'products' => SeminarType::cases(),
            'templates' => collect(SeminarType::cases())->mapWithKeys(fn (SeminarType $type): array => [$type->value => QuoteTexts::template($type)])->all(),
            'examples' => $examples,
            'placeholders' => QuoteTexts::PLACEHOLDERS,
            'canManage' => Gate::allows('seminars.prices'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];

        foreach (SeminarType::cases() as $type) {
            $rules['texts.'.$type->value.'.title'] = ['required', 'string', 'max:150'];
            $rules['texts.'.$type->value.'.content'] = ['required', 'string', 'max:1000'];
        }

        $data = $request->validate($rules, [
            'texts.*.title.required' => 'Bitte geben Sie den Titel ein.',
            'texts.*.title.max' => 'Der Titel darf höchstens 150 Zeichen lang sein.',
            'texts.*.content.required' => 'Bitte geben Sie die Beschreibung ein.',
            'texts.*.content.max' => 'Die Beschreibung darf höchstens 1000 Zeichen lang sein.',
        ]);

        $errors = [];

        foreach ($data['texts'] as $product => $text) {
            foreach (['title', 'content'] as $field) {
                $unknown = QuoteTexts::unknownPlaceholders($text[$field]);

                if ($unknown !== []) {
                    $errors['texts.'.$product.'.'.$field] = 'Unbekannter Platzhalter: {'.implode('}, {', $unknown).'}. Erlaubt sind '.implode(', ', array_map(fn (string $name): string => '{'.$name.'}', array_keys(QuoteTexts::PLACEHOLDERS))).'.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $texts = [];

        foreach ($data['texts'] as $product => $text) {
            $texts[$product] = ['title' => trim($text['title']), 'content' => implode("\n", QuoteTexts::lines($text['content']))];
        }

        $changed = $this->quotes->saveTexts($texts, $actor);

        return redirect()->route('seminar-texts.index')->with('success', $changed === [] ? 'Es gab keine Änderungen.' : 'Die Texte wurden gespeichert.');
    }
}
