<?php

namespace App\Console\Commands;

use App\Support\WebContentLibrary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class VdbsLibraryCheckCommand extends Command
{
    protected $signature = 'vdbs:library-check';

    protected $description =
        'Prüft Registry, Quellen und Inventarabdeckung der Web Content Library.';

    public function handle(
        WebContentLibrary $library,
    ): int {
        $categories = array_keys(
            $library->categories()
        );

        $statuses = array_keys(
            $library->statuses()
        );

        $items = $library->all();
        $errors = [];
        $ids = [];

        foreach ($items as $index => $item) {
            $prefix = 'Eintrag '.($index + 1);

            foreach ([
                'id',
                'name',
                'category',
                'status',
                'description',
                'source',
            ] as $requiredField) {
                if (
                    trim(
                        (string) ($item[$requiredField] ?? '')
                    ) === ''
                ) {
                    $errors[] =
                        "{$prefix}: Feld [{$requiredField}] fehlt.";
                }
            }

            foreach ([
                'files',
                'tags',
                'usage',
                'accessibility',
            ] as $requiredList) {
                if (
                    ! is_array(
                        $item[$requiredList] ?? null
                    )
                    || ($item[$requiredList] ?? []) === []
                ) {
                    $errors[] =
                        "{$prefix}: Liste [{$requiredList}] fehlt oder ist leer.";
                }
            }

            $id = trim(
                (string) ($item['id'] ?? ''),
            );

            if ($id === '') {
                continue;
            }

            if (in_array($id, $ids, true)) {
                $errors[] =
                    "{$prefix}: doppelte ID [{$id}].";
            }

            $ids[] = $id;

            if (
                ! in_array(
                    $item['category'] ?? null,
                    $categories,
                    true,
                )
            ) {
                $errors[] =
                    "{$prefix} [{$id}]: unbekannte Kategorie.";
            }

            if (
                ! in_array(
                    $item['status'] ?? null,
                    $statuses,
                    true,
                )
            ) {
                $errors[] =
                    "{$prefix} [{$id}]: unbekannter Status.";
            }

            $files = collect(
                $item['files'] ?? []
            )
                ->push(
                    $item['source'] ?? null
                )
                ->filter()
                ->unique();

            foreach ($files as $path) {
                if (
                    ! File::exists(
                        base_path($path),
                    )
                ) {
                    $errors[] =
                        "{$prefix} [{$id}]: Datei fehlt [{$path}].";
                }
            }
        }

        $coverage = $library->coverage();

        foreach ($coverage['missing'] as $path) {
            $errors[] =
                "Nicht inventarisierte VDBS-Datei: [{$path}].";
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $this->components->info(
            sprintf(
                'Web Content Library geprüft: %d Einträge, %d/%d Kern-Dateien inventarisiert.',
                $items->count(),
                $coverage['registered'],
                $coverage['discovered'],
            )
        );

        return self::SUCCESS;
    }
}
