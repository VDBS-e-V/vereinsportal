<?php

namespace App\Modules\Identity\Support;

use Illuminate\Support\Facades\RateLimiter;

final class LoginRateLimiter
{
    private const USER_MAX_ATTEMPTS = 5;

    private const IP_MAX_ATTEMPTS = 25;

    private const DECAY_SECONDS = 900;

    public function tooManyUserAttempts(string $email): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->userKey($email),
            self::USER_MAX_ATTEMPTS,
        );
    }

    public function tooManyIpAttempts(string $ipAddress): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->ipKey($ipAddress),
            self::IP_MAX_ATTEMPTS,
        );
    }

    public function hitFailure(
        string $email,
        string $ipAddress,
    ): void {
        RateLimiter::hit(
            $this->userKey($email),
            self::DECAY_SECONDS,
        );

        RateLimiter::hit(
            $this->ipKey($ipAddress),
            self::DECAY_SECONDS,
        );
    }

    public function clearUser(string $email): void
    {
        RateLimiter::clear(
            $this->userKey($email)
        );
    }

    public function userAvailableIn(string $email): int
    {
        return RateLimiter::availableIn(
            $this->userKey($email)
        );
    }

    public function ipAvailableIn(string $ipAddress): int
    {
        return RateLimiter::availableIn(
            $this->ipKey($ipAddress)
        );
    }

    private function userKey(string $email): string
    {
        return 'identity:login:user:'.hash(
            'sha256',
            EmailNormalizer::normalize($email),
        );
    }

    private function ipKey(string $ipAddress): string
    {
        return 'identity:login:ip:'.hash(
            'sha256',
            $ipAddress,
        );
    }
}