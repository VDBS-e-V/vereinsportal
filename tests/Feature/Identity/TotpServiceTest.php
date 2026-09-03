<?php

use App\Modules\Identity\Services\TotpService;

it('generates and verifies a totp code with a small time tolerance', function () {
    $service = app(TotpService::class);

    /*
     * RFC-6238-Testsecret für SHA1:
     * "12345678901234567890"
     */
    $secret =
        'GEZDGNBVGY3TQOJQ'
        .'GEZDGNBVGY3TQOJQ';

    /*
     * RFC-6238 liefert bei 59 Sekunden
     * 94287082 für 8 Stellen.
     * Bei 6 Stellen sind die letzten sechs:
     * 287082.
     */
    expect(
        $service->verify(
            secret: $secret,
            code: '287082',
            timestamp: 59,
        )
    )->toBeTrue();
});

it('rejects an invalid totp code', function () {
    $service = app(TotpService::class);

    $secret =
        $service->generateSecret();

    expect(
        $service->verify(
            secret: $secret,
            code: '000000',
            timestamp: 1_800_000_000,
        )
    )->toBeFalse();
});

it('creates an otpauth provisioning uri without exposing it elsewhere', function () {
    $service = app(TotpService::class);

    $secret =
        $service->generateSecret();

    $uri = $service->provisioningUri(
        account: 'erika@example.test',
        secret: $secret,
    );

    expect($uri)
        ->toStartWith('otpauth://totp/')
        ->and($uri)
        ->toContain(
            'secret='.$secret
        );
});