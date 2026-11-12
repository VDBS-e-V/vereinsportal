<?php

namespace App\Modules\Letters\Services;

use App\Modules\Letters\Models\LetterTemplate;
use App\Modules\Letters\Support\SampleLetter;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Legt die mitgelieferten Beispielvorlagen (SampleLetter::catalog) im Portal an. Jede wird nur einmal geladen: Wer sie später ändert oder löscht,
 * behält seinen Stand, beim nächsten Update kommt nichts zurück. Eine Vorlage mit gleichem Namen wird nie überschrieben.
 * Mit $restore werden fehlende Beispiele (nach Name) auch dann wieder angelegt, wenn sie schon einmal geladen waren.
 */
final class LetterDefaultsLoader
{
    public function __construct(private readonly LetterTemplates $templates) {}

    /**
     * @return array{created: int, skipped: int} neu angelegte und übersprungene Beispiele
     */
    public function load(bool $restore = false): array
    {
        $created = $skipped = 0;
        $loaded = DB::table('letter_template_defaults')->pluck('key')->all();

        foreach (SampleLetter::catalog() as $key => $sample) {
            $known = in_array($key, $loaded, true);

            if (($known && ! $restore) || LetterTemplate::query()->where('name', $sample['name'])->exists()) {
                $this->remember($key);
                $skipped++;

                continue;
            }

            $path = SampleLetter::build($sample['paragraphs']);

            try {
                $this->templates->store(new UploadedFile($path, 'Beispiel_'.$key.'.docx', null, null, true), $sample['name'], $sample['description'], $sample['audience'], null);
                $this->remember($key);
                $created++;
            } catch (DomainException) {
                $skipped++;
            } finally {
                @unlink($path);
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function remember(string $key): void
    {
        DB::table('letter_template_defaults')->updateOrInsert(['key' => $key], ['loaded_at' => now()]);
    }
}
