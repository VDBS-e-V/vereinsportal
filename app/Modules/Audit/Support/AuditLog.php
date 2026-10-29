<?php

namespace App\Modules\Audit\Support;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Schreibt Einträge ins Protokoll: AuditLog::record(AuditEvent::UserDisabled, $actor, $user, ['grund' => '…']).
 *
 * Der Kontext darf nur unkritische Angaben enthalten (Rollenname, geänderte Feldnamen, Methode), nie Passwörter, Codes,
 * Tokens oder Geheimnisse. Ein Fehler beim Protokollieren bricht die eigentliche Aktion nicht ab, wird aber gemeldet.
 */
final class AuditLog
{
    private const MAX_TEXT = 500;

    /** @param  array<string, mixed>  $context */
    public static function record(AuditEvent $event, ?User $actor = null, ?Model $subject = null, array $context = [], ?string $subjectLabel = null): void
    {
        try {
            [$type, $id, $label] = self::describeSubject($subject);

            AuditEntry::query()->create([
                'occurred_at' => now(),
                'event' => $event->value,
                'actor_user_id' => $actor?->id,
                'actor_label' => $actor !== null ? self::label($actor) : null,
                'subject_type' => $type,
                'subject_id' => $id,
                'subject_label' => $subjectLabel ?? $label,
                'context' => $context === [] ? null : self::clean($context),
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** Bezeichnung eines Nutzers fürs Protokoll: Name, sonst E-Mail-Adresse. */
    public static function label(User $user): string
    {
        $user->loadMissing('person');

        return mb_substr($user->person?->fullName() ?: $user->email, 0, 200);
    }

    /** @return array{0: ?string, 1: ?int, 2: ?string} */
    private static function describeSubject(?Model $subject): array
    {
        return match (true) {
            $subject instanceof User => ['user', $subject->id, self::label($subject)],
            $subject instanceof Person => ['person', $subject->id, mb_substr($subject->fullName(), 0, 200)],
            $subject instanceof Partner => ['partner', $subject->id, mb_substr($subject->name, 0, 200)],
            $subject instanceof RoleAssignment => ['user', $subject->user_id, ($subject->user instanceof User) ? self::label($subject->user) : null],
            default => [null, null, null],
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function clean(array $context): array
    {
        $clean = [];

        foreach ($context as $key => $value) {
            $clean[$key] = match (true) {
                is_string($value) => mb_substr($value, 0, self::MAX_TEXT),
                is_array($value) => self::clean($value),
                is_scalar($value) || $value === null => $value,
                default => null,
            };
        }

        return $clean;
    }
}
