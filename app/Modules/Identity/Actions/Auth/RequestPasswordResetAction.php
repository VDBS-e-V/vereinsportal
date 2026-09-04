<?php

namespace App\Modules\Identity\Actions\Auth;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Actions\QueueTemplatedEmailAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\EmailNormalizer;
use App\Modules\Identity\Support\PasswordResetUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Throwable;

final class RequestPasswordResetAction
{
    public function __construct(
        private readonly PasswordResetUrl $passwordResetUrl,
        private readonly QueueTemplatedEmailAction $queueEmail,
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        string $email,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): void {
        $email = EmailNormalizer::normalize($email);

        $user = User::query()
            ->where('email', $email)
            ->first();

        /*
         * Die Anfrage selbst wird immer auditiert.
         * Bei unbekannter E-Mail bleibt das Subject leer.
         * Kein Reset-Token und kein Passwortwert gelangen
         * ins Audit.
         */
        $this->auditWriter->write(
            eventKey: AuditEventCatalog::AUTH_PASSWORD_RESET_REQUESTED,
            actorType: $user !== null
                ? AuditActorType::User
                : AuditActorType::System,
            actorUserId: $user?->id,
            actorContext: 'password_reset_request',
            subjectType: $user !== null ? 'user' : null,
            subjectId: $user?->id,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceInfo: $deviceInfo,
        );

        /*
         * Öffentlich niemals offenlegen, warum kein Versand
         * erfolgt. Nur aktive und verifizierte Accounts
         * erhalten tatsächlich einen Reset-Link.
         */
        if (
            $user === null
            || $user->status !== UserStatus::Active
            || $user->email_verified_at === null
        ) {
            return;
        }

        try {
            DB::transaction(function () use (
                $email,
                $user,
            ): void {
                Password::broker()->sendResetLink(
                    [
                        'email' => $email,
                    ],
                    function (
                        User $resetUser,
                        string $token,
                    ) use ($user): void {
                        $expiresAt = now()->addMinutes(
                            (int) config(
                                'auth.passwords.users.expire',
                                60,
                            )
                        );

                        $this->queueEmail->execute(
                            templateKey: 'auth.password_reset',
                            recipientEmail: $resetUser->email,
                            values: [
                                'reset_url' => $this->passwordResetUrl
                                    ->create(
                                        $resetUser,
                                        $token,
                                    ),
                                'expires_at' => $expiresAt->format(
                                    'd.m.Y H:i'
                                ),
                                'first_name' => $user->person?->first_name
                                    ?? '',
                            ],
                        );
                    },
                );
            });
        } catch (Throwable $exception) {
            /*
             * Niemals durch unterschiedliche öffentliche
             * Antworten die Existenz eines Accounts verraten.
             *
             * Die Transaktion rollt Token/Delivery bei einem
             * fehlgeschlagenen Mail-Workflow zurück.
             */
            report($exception);
        }
    }
}
