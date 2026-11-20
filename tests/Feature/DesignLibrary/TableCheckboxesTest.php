<?php

use Illuminate\Support\Facades\Blade;

/** Alle Quelltexte der Ansichten (ohne die Beispiele der Design-Bibliothek und ohne die Bausteine, die das Kästchen selbst erzeugen). */
function tcViews(): array
{
    $files = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)) as $file) {
        $path = str_replace('\\', '/', $file->getPathname());

        if (! str_ends_with($path, '.blade.php') || str_contains($path, '/design-library/entries/') || str_ends_with($path, '/components/vdbs/data/table.blade.php') || str_ends_with($path, '/components/vdbs/data/table-check.blade.php')) {
            continue;
        }

        $files[$path] = (string) file_get_contents($path);
    }

    return $files;
}

/** @return list<string> */
function tcTables(string $code): array
{
    preg_match_all('#<x-vdbs\.data\.table\b.*?</x-vdbs\.data\.table>|<table\b.*?</table>#s', $code, $blocks);

    return $blocks[0];
}

it('uses the own checkbox design for every tick in a table', function () {
    $violations = [];

    foreach (tcViews() as $path => $code) {
        foreach (tcTables($code) as $table) {
            // rohe Kästchen oder die Standard-Kästchen mit Beschriftung gehören nicht in Tabellen: Dort gilt <x-vdbs.data.table-check>
            if (str_contains($table, 'type="checkbox"') || preg_match('#<x-vdbs\.forms\.(checkbox|switch|checkbox-group|multi-select|radio-group)\b#', $table)) {
                $violations[] = str_replace(resource_path('views/'), '', $path);
            }
        }
    }

    expect(array_values(array_unique($violations)))->toBe([]);
});

it('gives every table with a row selection a select-all box and a count', function () {
    $violations = [];

    foreach (tcViews() as $path => $code) {
        $selectable = false;

        foreach (tcTables($code) as $table) {
            // Zeilenauswahl: Kästchen mit form="…"; reine Schalter in einer Zeile haben kein form
            if (preg_match('#<x-vdbs\.data\.table-check\b(?:(?!/>).)*?\s:?form=#s', $table)) {
                $selectable = true;
            }
        }

        if ($selectable && (! str_contains($code, "'selectAll'") || ! str_contains($code, 'data-vdbs-select-count'))) {
            $violations[] = str_replace(resource_path('views/'), '', $path);
        }
    }

    expect($violations)->toBe([]);
});

it('renders the table check with an empty cell when the row cannot be chosen', function () {
    $html = Blade::render('<x-vdbs.data.table-check name="ids[]" value="5" label="Nicht wählbar" form="bulk" :show="false" />');

    expect($html)->toContain('<td class="vdbs-table__select">')->not->toContain('<input')->not->toContain('vdbs-choice');

    $disabled = Blade::render('<x-vdbs.data.table-check name="ids[]" value="5" label="Gesperrt" form="bulk" :disabled="true" />');

    expect($disabled)->toContain('disabled');
});
