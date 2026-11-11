<?php

namespace App\Modules\Sso\Support;

use App\Modules\Access\Models\User;
use App\Modules\Sso\Services\OAuthKeys;
use App\Modules\Sso\Services\OidcClaims;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;

/** Antwort der Token-Adresse: bei Scope „openid“ kommt zusätzlich ein signiertes ID-Token (RS256) mit. */
class IdTokenResponse extends BearerTokenResponse
{
    public static function nonceKey(string $clientId, int|string $userId): string
    {
        return 'oidc:nonce:'.$clientId.':'.$userId;
    }

    /** {@inheritdoc} */
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        $scopes = array_map(fn ($scope): string => $scope->getIdentifier(), $accessToken->getScopes());

        if (! in_array('openid', $scopes, true)) {
            return [];
        }

        $user = User::query()->find($accessToken->getUserIdentifier());

        if ($user === null) {
            return [];
        }

        $keys = app(OAuthKeys::class);
        $clientId = (string) $accessToken->getClient()->getIdentifier();
        $now = new DateTimeImmutable;
        $expires = DateTimeImmutable::createFromInterface($accessToken->getExpiryDateTime());

        $config = Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($this->privateKey->getKeyContents(), (string) $this->privateKey->getPassPhrase()),
            InMemory::plainText((string) file_get_contents($keys->publicPath())),
        );

        $builder = $config->builder()
            ->issuedBy(rtrim(url('/'), '/'))
            ->permittedFor($clientId)
            ->issuedAt($now)
            ->expiresAt($expires)
            ->withHeader('kid', (string) $keys->keyId())
            ->withClaim('auth_time', $now->getTimestamp());

        foreach (app(OidcClaims::class)->forUser($user, $scopes) as $name => $value) {
            $builder = $name === 'sub' ? $builder->relatedTo((string) $value) : $builder->withClaim($name, $value);
        }

        $nonce = Cache::pull(self::nonceKey($clientId, $user->id));

        if (is_string($nonce) && $nonce !== '') {
            $builder = $builder->withClaim('nonce', $nonce);
        }

        return ['id_token' => $builder->getToken($config->signer(), $config->signingKey())->toString()];
    }
}
