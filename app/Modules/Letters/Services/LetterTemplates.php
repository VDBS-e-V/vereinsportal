<?php

namespace App\Modules\Letters\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Letters\Models\LetterTemplate;
use App\Modules\Letters\Support\LetterVariables;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;
use ZipArchive;

/** Word-Vorlagen (.docx) mit Platzhaltern: prüfen, ablegen, ersetzen, löschen. Die Dateien liegen privat. */
final class LetterTemplates
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    private const MAX_UNPACKED_BYTES = 60 * 1024 * 1024;

    private const MAX_ENTRIES = 600;

    public function absolutePath(LetterTemplate $template): string
    {
        return Storage::disk(LetterTemplate::DISK)->path($template->path);
    }

    /**
     * Prüft eine Word-Datei und liest ihre Platzhalter (Hauptteil, Kopf- und Fußzeilen).
     *
     * @return list<string>
     *
     * @throws DomainException
     */
    public function inspect(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true || $zip->locateName('word/document.xml') === false) {
            throw new DomainException('Das ist keine gültige Word-Datei (.docx). Speichern Sie die Vorlage in Word als „Word-Dokument (*.docx)“.');
        }

        $unpacked = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $unpacked += (int) ($zip->statIndex($i)['size'] ?? 0);
        }

        $macro = $zip->locateName('word/vbaProject.bin') !== false;
        $entries = $zip->numFiles;
        $zip->close();

        if ($macro) {
            throw new DomainException('Die Datei enthält Makros. Bitte speichern Sie die Vorlage als normales Word-Dokument (.docx) ohne Makros.');
        }

        if ($entries > self::MAX_ENTRIES || $unpacked > self::MAX_UNPACKED_BYTES) {
            throw new DomainException('Die Datei ist für eine Vorlage zu umfangreich.');
        }

        if (! class_exists(TemplateProcessor::class)) {
            throw new DomainException('Die Komponente für Word-Dateien (phpoffice/phpword) ist nicht installiert. Bitte „composer install“ ausführen.');
        }

        try {
            $variables = (new TemplateProcessor($path))->getVariables();
        } catch (Throwable $exception) {
            report($exception);

            throw new DomainException('Die Word-Datei lässt sich nicht lesen. Öffnen Sie sie in Word und speichern Sie sie neu als .docx.', 0, $exception);
        }

        return array_values(array_unique(array_map('strval', $variables)));
    }

    /** Platzhalter der Vorlage, die das Portal nicht kennt (meist Tippfehler). @param  list<string>  $variables @return list<string> */
    public function unknown(array $variables): array
    {
        return array_values(array_filter($variables, fn (string $name): bool => ! in_array(LetterVariables::baseName($name), LetterVariables::known(), true)));
    }

    /** @throws DomainException */
    public function store(UploadedFile $file, string $name, ?string $description, string $audience, ?User $by): LetterTemplate
    {
        $name = trim($name);

        if (LetterTemplate::query()->where('name', $name)->exists()) {
            throw new DomainException('Es gibt schon eine Vorlage mit diesem Namen.');
        }

        [$path, $variables] = $this->keep($file);

        $template = LetterTemplate::query()->create([
            'name' => $name, 'description' => filled($description) ? trim((string) $description) : null,
            'audience' => array_key_exists($audience, LetterTemplate::AUDIENCES) ? $audience : 'general',
            'path' => $path, 'original_name' => $this->safeName($file), 'size' => (int) $file->getSize(), 'variables' => $variables,
            'created_by_user_id' => $by?->id, 'updated_by_user_id' => $by?->id,
        ]);

        AuditLog::record(AuditEvent::LetterTemplateSaved, $by, null, [], $by === null ? 'Beispielvorlage geladen' : 'Briefvorlage angelegt');

        return $template;
    }

    /** @throws DomainException */
    public function replaceFile(LetterTemplate $template, UploadedFile $file, User $by): LetterTemplate
    {
        [$path, $variables] = $this->keep($file);
        Storage::disk(LetterTemplate::DISK)->delete($template->path);
        $template->forceFill(['path' => $path, 'original_name' => $this->safeName($file), 'size' => (int) $file->getSize(), 'variables' => $variables, 'updated_by_user_id' => $by->id])->save();
        AuditLog::record(AuditEvent::LetterTemplateSaved, $by, null, [], 'Briefvorlage ersetzt');

        return $template;
    }

    /** @throws DomainException */
    public function updateDetails(LetterTemplate $template, string $name, ?string $description, string $audience, User $by): LetterTemplate
    {
        $name = trim($name);

        if (LetterTemplate::query()->where('name', $name)->whereKeyNot($template->id)->exists()) {
            throw new DomainException('Es gibt schon eine Vorlage mit diesem Namen.');
        }

        $template->forceFill(['name' => $name, 'description' => filled($description) ? trim((string) $description) : null, 'audience' => array_key_exists($audience, LetterTemplate::AUDIENCES) ? $audience : $template->audience, 'updated_by_user_id' => $by->id])->save();

        return $template;
    }

    public function delete(LetterTemplate $template, User $by): void
    {
        Storage::disk(LetterTemplate::DISK)->delete($template->path);
        $template->delete();
        AuditLog::record(AuditEvent::LetterTemplateSaved, $by, null, [], 'Briefvorlage gelöscht');
    }

    /** @return array{0: string, 1: list<string>} Pfad auf dem privaten Speicher, Platzhalter */
    private function keep(UploadedFile $file): array
    {
        if (strtolower((string) $file->getClientOriginalExtension()) !== 'docx') {
            throw new DomainException('Bitte laden Sie eine Word-Datei mit der Endung .docx hoch.');
        }

        if ((int) $file->getSize() > self::MAX_BYTES) {
            throw new DomainException('Die Datei darf höchstens '.round(self::MAX_BYTES / 1048576).' MB groß sein.');
        }

        $variables = $this->inspect((string) $file->getRealPath());
        $path = 'letters/templates/'.Str::random(40).'.docx';
        Storage::disk(LetterTemplate::DISK)->putFileAs(dirname($path), $file, basename($path));

        return [$path, $variables];
    }

    private function safeName(UploadedFile $file): string
    {
        return Str::limit((string) (preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', (string) $file->getClientOriginalName()) ?: 'Vorlage.docx'), 190, '');
    }
}
