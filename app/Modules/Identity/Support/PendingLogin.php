<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;

final class PendingLogin
{
    private const SESSION_KEY =
        'identity.pending_login';

    private const LIFETIME_SECONDS =
        10 * 60;

    public function start(
        User $user,
        bool $remember,
    ): void {
        session()->put(
            self::SESSION_KEY,
            [
                'user_id' => $user->id,
                'session_version' =>
                    $user->session_version,
                'remember' => $remember,
                'started_at' => now()->timestamp,
            ],
        );
    }

    public function exists(): bool
    {
        return $this->data() !== null;
    }

    /**
     * @return array{
     *     user_id:int,
     *     session_version:int,
     *     remember:bool,
     *     started_at:int
     * }|null
     */
    public function data(): ?array
    {
        $data = session()->get(
            self::SESSION_KEY
        );

        if (! is_array($data)) {
            return null;
        }

        if (
            ! isset(
                $data['user_id'],
                $data['session_version'],
                $data['remember'],
                $data['started_at'],
            )
        ) {
            $this->clear();

            return null;
        }

        $startedAt =
            (int) $data['started_at'];

        if (
            $startedAt
            < now()
                ->subSeconds(
                    self::LIFETIME_SECONDS
                )
                ->timestamp
        ) {
            $this->clear();

            return null;
        }

        return [
            'user_id' =>
                (int) $data['user_id'],
            'session_version' =>
                (int) $data['session_version'],
            'remember' =>
                (bool) $data['remember'],
            'started_at' => $startedAt,
        ];
    }

    public function user(): ?User
    {
        $data = $this->data();

        if ($data === null) {
            return null;
        }

        $user = User::query()
            ->find($data['user_id']);

        if (
            $user === null
            || $user->status
                !== UserStatus::Active
            || $user->email_verified_at === null
            || $user->session_version
                !== $data['session_version']
        ) {
            $this->clear();

            return null;
        }

        return $user;
    }

    public function clear(): void
    {
        session()->forget(
            self::SESSION_KEY
        );
    }
}