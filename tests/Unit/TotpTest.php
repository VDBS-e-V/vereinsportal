<?php

use App\Modules\Access\Services\Totp;

// Testwerte aus RFC 6238, Anhang B (SHA-1, Schlüssel "12345678901234567890"), auf sechs Stellen gekürzt.
dataset('rfc6238', [
    [59, '287082'],
    [1111111109, '081804'],
    [1111111111, '050471'],
    [1234567890, '005924'],
    [2000000000, '279037'],
    [20000000000, '353130'],
]);

it('produces the codes of the RFC 6238 test vectors', function (int $time, string $expected) {
    $secret = Totp::base32Encode('12345678901234567890');

    expect(Totp::code($secret, $time))->toBe($expected);
})->with('rfc6238');

it('encodes and decodes base32 without loss', function () {
    $binary = random_bytes(20);

    expect(Totp::base32Decode(Totp::base32Encode($binary)))->toBe($binary)
        ->and(Totp::base32Encode('12345678901234567890'))->toBe('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ');
});

it('generates a 32 character secret from the base32 alphabet', function () {
    expect(Totp::generateSecret())->toMatch('/^[A-Z2-7]{32}$/')
        ->and(Totp::generateSecret())->not->toBe(Totp::generateSecret());
});

it('accepts codes of the neighbouring time windows but not older ones', function () {
    $secret = Totp::base32Encode('12345678901234567890');
    $code = Totp::code($secret, 1000);

    expect(Totp::verify($secret, $code, 1000))->toBe(intdiv(1000, 30))
        ->and(Totp::verify($secret, $code, 1000 + 30))->toBe(intdiv(1000, 30))
        ->and(Totp::verify($secret, $code, 1000 - 30))->toBe(intdiv(1000, 30))
        ->and(Totp::verify($secret, $code, 1000 + 120))->toBeNull();
});

it('ignores spaces and rejects malformed or wrong codes', function () {
    $secret = Totp::base32Encode('12345678901234567890');

    expect(Totp::verify($secret, '287 082', 59))->not->toBeNull()
        ->and(Totp::verify($secret, '28708', 59))->toBeNull()
        ->and(Totp::verify($secret, '2870821', 59))->toBeNull()
        ->and(Totp::verify($secret, '000000', 59))->toBeNull()
        ->and(Totp::verify($secret, 'abcdef', 59))->toBeNull();
});

it('builds an otpauth address and groups the secret for typing', function () {
    $uri = Totp::uri('ABCDEFGH', 'erika@example.org', 'VDBS Portal');

    expect($uri)->toStartWith('otpauth://totp/VDBS%20Portal%3Aerika%40example.org?')
        ->toContain('secret=ABCDEFGH')
        ->toContain('issuer=VDBS%20Portal')
        ->toContain('digits=6')
        ->toContain('period=30');
    expect(Totp::format('ABCDEFGHIJKL'))->toBe('ABCD EFGH IJKL');
});
