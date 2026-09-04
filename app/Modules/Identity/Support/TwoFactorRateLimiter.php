<?php

namespace App\Modules\Identity\Support;

use Illuminate\Support\Facades\RateLimiter;

final class TwoFactorRateLimiter
{
    public const USER_MAX_ATTEMPTS = 5;

    public const IP_MAX_ATTEMPTS = 25;

    public const DECAY_SECONDS = 15 * 60;

    public function tooManyUserAttempts(
        int $userId,
    ): bool {
        return RateLimiter::tooManyAttempts(
            $this->userKey($userId),
            self::USER_MAX_ATTEMPTS,
        );
    }

    public function tooManyIpAttempts(
        string $ipAddress,
    ): bool {
        return RateLimiter::tooManyAttempts(
            $this->ipKey($ipAddress),
            self::IP_MAX_ATTEMPTS,
        );
    }

    public function hitFailure(
        int $userId,
        string $ipAddress,
    ): void {
        RateLimiter::hit(
            $this->userKey($userId),
            self::DECAY_SECONDS,
        );

        RateLimiter::hit(
            $this->ipKey($ipAddress),
            self::DECAY_SECONDS,
        );
    }

    public function clearUser(
        int $userId,
    ): void {
        RateLimiter::clear(
            $this->userKey($userId),
        );
    }

    public function userAvailableIn(
        int $userId,
    ): int {
        return RateLimiter::availableIn(
            $this->userKey($userId),
        );
    }

    public function ipAvailableIn(
        string $ipAddress,
    ): int {
        return RateLimiter::availableIn(
            $this->ipKey($ipAddress),
        );
    }

    private function userKey(
        int $userId,
    ): string {
        return 'identity:2fa:user:'.$userId;
    }

    private function ipKey(
        string $ipAddress,
    ): string {
        return 'identity:2fa:ip:'.hash(
            'sha256',
            $ipAddress,
        );
    }
}
