<?php

namespace App\Modules\Feedback\Services;

use App\Modules\Feedback\Models\FeedbackLink;
use App\Modules\Feedback\Models\FeedbackResponse;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Prüft die Antworten gegen die Fassung des Formulars, die der Link festhält, und speichert sie ohne jede Kennung. */
final class FeedbackSubmissions
{
    public function __construct(private readonly FeedbackForms $forms, private readonly FeedbackLinks $links) {}

    /**
     * @param  array<string, mixed>  $input  Felder a_<id>
     *
     * @throws ValidationException
     * @throws DomainException wenn der Link nicht (mehr) offen ist
     */
    public function submit(FeedbackLink $link, array $input): FeedbackResponse
    {
        $questions = $this->questions($link);
        $answers = [];
        $name = null;
        $errors = [];

        foreach ($questions as $question) {
            $key = 'a_'.$question['id'];
            $raw = $input[$key] ?? null;
            $type = $question['type'];
            $required = ! empty($question['required']);

            if ($type === 'multi') {
                $values = array_values(array_filter((array) $raw, 'is_string'));
                $valid = array_values(array_intersect($values, $question['options'] ?? []));

                if ($valid === [] && $required) {
                    $errors[$key] = 'Bitte wählen Sie mindestens eine Antwort.';
                } elseif ($valid !== []) {
                    $answers[$question['id']] = $valid;
                }

                continue;
            }

            $value = is_string($raw) ? trim($raw) : (is_int($raw) ? (string) $raw : '');

            if ($value === '') {
                if ($required) {
                    $errors[$key] = 'Bitte beantworten Sie diese Frage.';
                }

                continue;
            }

            switch ($type) {
                case 'scale':
                    if (! in_array($value, ['1', '2', '3', '4', '5'], true)) {
                        $errors[$key] = 'Bitte wählen Sie einen Wert von 1 bis 5.';
                    } else {
                        $answers[$question['id']] = (int) $value;
                    }
                    break;
                case 'yesno':
                    if (! in_array($value, ['yes', 'no'], true)) {
                        $errors[$key] = 'Bitte wählen Sie Ja oder Nein.';
                    } else {
                        $answers[$question['id']] = $value;
                    }
                    break;
                case 'choice':
                    if (! in_array($value, $question['options'] ?? [], true)) {
                        $errors[$key] = 'Bitte wählen Sie eine der Antworten.';
                    } else {
                        $answers[$question['id']] = $value;
                    }
                    break;
                case 'text':
                    $answers[$question['id']] = mb_substr($value, 0, 2000);
                    break;
                case 'name':
                    $name = mb_substr($value, 0, 150);
                    break;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($answers === []) {
            throw ValidationException::withMessages(['form' => 'Bitte beantworten Sie mindestens eine Frage.']);
        }

        return DB::transaction(function () use ($link, $answers, $name): FeedbackResponse {
            // Sperre auf dem Link, damit die Obergrenze auch bei gleichzeitigen Antworten hält.
            $locked = FeedbackLink::query()->whereKey($link->id)->lockForUpdate()->first();

            if ($locked === null || $this->links->state($locked) !== 'open') {
                throw new DomainException('Dieser Link nimmt keine Antworten mehr an.');
            }

            return FeedbackResponse::query()->create(['link_id' => $locked->id, 'submitted_on' => now()->toDateString(), 'answers' => $answers, 'respondent_name' => $name]);
        });
    }

    /** @return list<array<string, mixed>> */
    public function questions(FeedbackLink $link): array
    {
        $version = $this->forms->version($link->form_id, $link->form_version);

        return $version !== null ? array_values((array) $version->questions) : [];
    }
}
