<?php

use App\Modules\Sso\Services\OAuthKeys;
use Illuminate\Support\Facades\File;
use Laravel\Passport\Passport;

it('creates a matching key pair with OpenSSL without the passport commands and keeps existing keys', function () {
    $directory = storage_path('framework/testing/oauth-keys-'.bin2hex(random_bytes(4)));
    File::ensureDirectoryExists($directory);
    $original = Passport::keyPath('oauth-private.key');
    Passport::loadKeysFrom($directory);

    try {
        $keys = app(OAuthKeys::class);

        expect($keys->exists())->toBeFalse()->and($keys->ensure())->toBeTrue()->and($keys->exists())->toBeTrue();

        $signature = '';
        openssl_sign('portal', $signature, openssl_pkey_get_private((string) file_get_contents($keys->privatePath())), OPENSSL_ALGO_SHA256);

        expect(openssl_verify('portal', $signature, openssl_pkey_get_public((string) file_get_contents($keys->publicPath())), OPENSSL_ALGO_SHA256))->toBe(1)
            ->and($keys->ensure())->toBeFalse()
            ->and($keys->jwk()['kty'])->toBe('RSA');
    } finally {
        Passport::loadKeysFrom(dirname($original));
        File::deleteDirectory($directory);
    }
});
