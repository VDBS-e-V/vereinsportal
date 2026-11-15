<?php

use Symfony\Component\Finder\Finder;

// Ein doppeltes Anführungszeichen mitten in einem gebundenen Attribut (:value="implode("\n", …)") beendet das Attribut früh:
// der Rest der Komponente erscheint dann als Text auf der Seite.
it('has no double quotes inside bound attributes of components', function () {
    $offenders = [];

    foreach ((new Finder)->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
        foreach (preg_split('/\R/', $file->getContents()) ?: [] as $number => $line) {
            if (str_contains($line, '<x-') && preg_match('/:[\w-]+="[^"]*[(,.=]\s*"/', $line) === 1) {
                $offenders[] = $file->getRelativePathname().':'.($number + 1);
            }
        }
    }

    expect($offenders)->toBe([]);
});
