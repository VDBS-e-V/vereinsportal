<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Models\TwoFactorRecoveryCode;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Hash;

final class RecoveryCodeService
{
    /**
     * @return list<string>
     */
    public function replaceFor(
        User $user,
    ): array {
        TwoFactorRecoveryCode::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->update([
                'invalidated_at' => now(),
            ]);

        $plainCodes = [];

        for ($index = 0; $index < 4; $index++) {
            $plainCode =
                $this->generatePlainCode();

            TwoFactorRecoveryCode::query()
                ->create([
                    'user_id' =>
                        $user->id,
                    'code_hash' =>
                        Hash::make(
                            $this->normalize(
                                $plainCode
                            )
                        ),
                ]);

            $plainCodes[] = $plainCode;
        }

        return $plainCodes;
    }

    public function matches(
        string $plainCode,
        string $hash,
    ): bool {
        return Hash::check(
            $this->normalize($plainCode),
            $hash,
        );
    }

    private function generatePlainCode(): string
    {
        $raw = strtoupper(
            bin2hex(
                random_bytes(8)
            )
        );

        return implode(
            '-',
            str_split($raw, 4),
        );
    }

    private function normalize(
        string $code,
    ): string {
        return strtoupper(
            preg_replace(
                '/[^A-Z0-9]/',
                '',
                $code,
            ) ?? ''
        );
    }
}