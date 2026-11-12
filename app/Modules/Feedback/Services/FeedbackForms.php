<?php

namespace App\Modules\Feedback\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Feedback\Models\FeedbackForm;
use App\Modules\Feedback\Models\FeedbackFormVersion;
use App\Modules\Feedback\Support\FeedbackDefaults;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Formulare laden, bearbeiten (jede Änderung erzeugt eine neue Fassung) und auf den Standard zurücksetzen. */
final class FeedbackForms
{
    private const MAX_QUESTIONS = 40;

    /** Legt die Standardformulare an, wenn sie fehlen (wiederholbar, ändert nichts Vorhandenes). */
    public function ensureDefaults(): void
    {
        foreach (FeedbackDefaults::forms() as $data) {
            if (FeedbackForm::query()->where('key', $data['key'])->exists()) {
                continue;
            }

            DB::transaction(function () use ($data): void {
                $form = FeedbackForm::query()->create($data + ['version' => 1]);
                $this->snapshot($form);
            });
        }
    }

    /** @return Collection<int, FeedbackForm> */
    public function all(): Collection
    {
        $this->ensureDefaults();

        return FeedbackForm::query()->orderBy('id')->get();
    }

    public function forAudience(string $audience): ?FeedbackForm
    {
        $this->ensureDefaults();

        return FeedbackForm::query()->where('audience', $audience)->where('active', true)->orderBy('id')->first();
    }

    public function version(int $formId, int $version): ?FeedbackFormVersion
    {
        return FeedbackFormVersion::query()->where('form_id', $formId)->where('version', $version)->first();
    }

    /**
     * Speichert Titel, Einleitung und die bearbeiteten Fragen als neue Fassung.
     *
     * @param  array<string, mixed>  $input  title, intro, q[<id>][text|hint|required|options|position|remove], new_type, new_text
     *
     * @throws DomainException
     */
    public function save(FeedbackForm $form, array $input, User $by): FeedbackForm
    {
        $title = trim((string) ($input['title'] ?? ''));

        if ($title === '') {
            throw new DomainException('Bitte geben Sie dem Formular einen Titel.');
        }

        $edited = (array) ($input['q'] ?? []);
        $questions = [];

        foreach ($form->questions as $question) {
            $row = (array) ($edited[$question['id']] ?? []);

            if (! empty($row['remove'])) {
                continue;
            }

            $text = trim((string) ($row['text'] ?? $question['text']));

            if ($text === '') {
                throw new DomainException('Eine Frage braucht einen Text. Entfernen Sie die Frage, wenn sie wegfallen soll.');
            }

            $question['text'] = Str::limit($text, 300, '');
            $question['hint'] = Str::limit(trim((string) ($row['hint'] ?? ($question['hint'] ?? ''))), 300, '');
            $question['required'] = $question['type'] === 'name' || $question['type'] === 'text' ? ! empty($row['required']) : ! empty($row['required']);

            if (in_array($question['type'], ['choice', 'multi'], true)) {
                $question['options'] = $this->options((string) ($row['options'] ?? implode("\n", (array) ($question['options'] ?? []))));
            }

            $question['_position'] = (int) ($row['position'] ?? (count($questions) + 1));
            $questions[] = $question;
        }

        usort($questions, fn (array $a, array $b): int => $a['_position'] <=> $b['_position']);
        $questions = array_map(function (array $question): array {
            unset($question['_position']);

            return $question;
        }, $questions);

        $newText = trim((string) ($input['new_text'] ?? ''));
        $newType = (string) ($input['new_type'] ?? '');

        if ($newText !== '') {
            if (! array_key_exists($newType, FeedbackDefaults::TYPES)) {
                throw new DomainException('Bitte wählen Sie den Typ der neuen Frage.');
            }

            $new = ['id' => $this->nextId($form, $questions), 'type' => $newType, 'text' => Str::limit($newText, 300, ''), 'hint' => '', 'required' => false];

            if (in_array($newType, ['choice', 'multi'], true)) {
                $new['options'] = $this->options((string) ($input['new_options'] ?? ''));
            }

            if ($newType === 'scale') {
                $new['labels'] = FeedbackDefaults::SCALE_LABELS;
                $new['smileys'] = false;
            }

            $questions[] = $new;
        }

        if ($questions === []) {
            throw new DomainException('Ein Formular braucht mindestens eine Frage.');
        }

        if (count($questions) > self::MAX_QUESTIONS) {
            throw new DomainException('Ein Formular darf höchstens '.self::MAX_QUESTIONS.' Fragen haben.');
        }

        return DB::transaction(function () use ($form, $title, $input, $questions, $by): FeedbackForm {
            $form->forceFill(['title' => Str::limit($title, 200, ''), 'intro' => Str::limit(trim((string) ($input['intro'] ?? '')), 1000, ''), 'questions' => array_values($questions), 'version' => $form->version + 1, 'updated_by_user_id' => $by->id])->save();
            $this->snapshot($form);
            AuditLog::record(AuditEvent::FeedbackFormSaved, $by, null, ['formular' => $form->audienceLabel(), 'fassung' => $form->version]);

            return $form;
        });
    }

    /** Setzt das Formular auf den mitgelieferten Standard zurück (neue Fassung). */
    public function reset(FeedbackForm $form, User $by): FeedbackForm
    {
        $default = FeedbackDefaults::forms()[$form->key] ?? null;

        if ($default === null) {
            throw new DomainException('Für dieses Formular gibt es keinen Standard.');
        }

        return DB::transaction(function () use ($form, $default, $by): FeedbackForm {
            $form->forceFill(['title' => $default['title'], 'intro' => $default['intro'], 'questions' => $default['questions'], 'version' => $form->version + 1, 'updated_by_user_id' => $by->id])->save();
            $this->snapshot($form);
            AuditLog::record(AuditEvent::FeedbackFormSaved, $by, null, ['formular' => $form->audienceLabel(), 'fassung' => $form->version, 'zurueckgesetzt' => true]);

            return $form;
        });
    }

    private function snapshot(FeedbackForm $form): void
    {
        FeedbackFormVersion::query()->updateOrCreate(['form_id' => $form->id, 'version' => $form->version], ['title' => $form->title, 'intro' => $form->intro, 'questions' => $form->questions, 'created_at' => now()]);
    }

    /** @return list<string> */
    private function options(string $text): array
    {
        $options = array_values(array_unique(array_filter(array_map(fn (string $line): string => Str::limit(trim($line), 100, ''), preg_split('/\R/', $text) ?: []), fn (string $line): bool => $line !== '')));

        if (count($options) < 2 || count($options) > 12) {
            throw new DomainException('Eine Auswahlfrage braucht zwischen 2 und 12 Antworten, eine pro Zeile.');
        }

        return $options;
    }

    /** @param  list<array<string, mixed>>  $questions */
    private function nextId(FeedbackForm $form, array $questions): string
    {
        $numbers = array_map(fn (array $question): int => (int) preg_replace('/\D/', '', (string) $question['id']), array_merge($form->questions, $questions));

        return 'q'.(max($numbers ?: [0]) + 1);
    }
}
