<?php

use App\Modules\Identity\Support\EmailNormalizer;

it('normalizes email addresses', function () {
    expect(
        EmailNormalizer::normalize('  Erika.Mustermann@Example.TEST  ')
    )->toBe('erika.mustermann@example.test');
});
