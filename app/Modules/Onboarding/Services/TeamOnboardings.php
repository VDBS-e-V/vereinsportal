<?php

namespace App\Modules\Onboarding\Services;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Onboarding\Enums\TeamStep;
use App\Modules\Onboarding\Mail\TeamOnboardingStartedMail;
use App\Modules\Onboarding\Models\TeamOnboarding;
use App\Modules\Onboarding\Models\TeamOnboardingStep;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Onboarding der Teamenden: Die Koordination vergibt die Rolle Teamende, damit beginnt die Einarbeitung (vier Schritte). Masernschutz und
 * Führungszeugnis trägt die Koordination ein, Verpflichtungserklärung und Verhaltenskodex bestätigen die Teamenden selbst. Sind alle Schritte
 * erledigt, gilt die Einarbeitung als abgeschlossen. Im allgemeinen Protokoll stehen nur Beginn und Abschluss, nie einzelne Schritte.
 */
final class TeamOnboardings
{
    public function __construct(private readonly OnboardingDocuments $documents) {}

    /** Legt die Einarbeitung an, wenn das Konto die Rolle Teamende hat und noch keine besteht. */
    public function ensure(User $user, ?User $by = null): ?TeamOnboarding
    {
        if (! in_array(RoleKey::Team->value, Capabilities::activeRoleKeys($user), true)) {
            return null;
        }

        $existing = $user->teamOnboarding()->with('steps')->first();

        if ($existing instanceof TeamOnboarding) {
            return $existing;
        }

        $onboarding = DB::transaction(function () use ($user, $by): TeamOnboarding {
            $onboarding = TeamOnboarding::query()->create(['user_id' => $user->id, 'started_at' => now(), 'started_by_user_id' => $by?->id]);

            foreach (TeamStep::cases() as $step) {
                $onboarding->steps()->create(['step' => $step->value]);
            }

            return $onboarding->load('steps');
        });

        AuditLog::record(AuditEvent::TeamOnboardingStarted, $by, $user);

        try {
            Mail::to($user->email)->send(new TeamOnboardingStartedMail(route('team-self.index')));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $onboarding;
    }

    /** Legt die Einarbeitung für alle Konten mit der Rolle Teamende an, die noch keine haben. */
    public function ensureAll(?User $by = null): void
    {
        User::query()
            ->whereDoesntHave('teamOnboarding')
            ->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', RoleKey::Team->value)))
            ->get()
            ->each(fn (User $user) => $this->ensure($user, $by));
    }

    /**
     * Die Teamenden bestätigen einen Schritt selbst (nur Verpflichtungserklärung und Verhaltenskodex), nachdem das Dokument bereitsteht.
     *
     * @throws DomainException
     */
    public function confirmOwn(User $user, TeamStep $step): void
    {
        $onboarding = $this->ensure($user);

        if (! $onboarding instanceof TeamOnboarding) {
            throw new DomainException('Für Ihr Konto gibt es keine Einarbeitung.');
        }

        if (! $step->bySelf()) {
            throw new DomainException('Diesen Schritt trägt die Koordination ein.');
        }

        $document = $this->documents->current($step->documentKind());

        if ($document === null) {
            throw new DomainException('Das Dokument wird von der Koordination noch bereitgestellt.');
        }

        $row = $onboarding->step($step);

        if ($row->isDone()) {
            return;
        }

        $row->forceFill(['done_at' => now(), 'done_by_user_id' => $user->id, 'by_self' => true, 'document_id' => $document->id])->save();

        $this->refresh($onboarding, $user);
    }

    /** Die Koordination trägt einen Schritt ein (auch stellvertretend für die Selbstbestätigung). */
    public function markDone(TeamOnboarding $onboarding, TeamStep $step, User $by, ?string $date = null, ?string $note = null): void
    {
        $row = $onboarding->step($step);

        $document = $step->documentKind() !== null ? $this->documents->current($step->documentKind()) : null;

        $row->forceFill([
            'done_at' => now(),
            'done_by_user_id' => $by->id,
            'by_self' => false,
            'document_id' => $document?->id,
            'data' => filled($date) ? ['datum' => $date] : null,
            'note' => filled($note) ? trim($note) : null,
        ])->save();

        $this->refresh($onboarding, $by);
    }

    /** Nimmt einen eingetragenen Schritt zurück. */
    public function reopen(TeamOnboarding $onboarding, TeamStep $step, User $by): void
    {
        $onboarding->step($step)->forceFill(['done_at' => null, 'done_by_user_id' => null, 'by_self' => false, 'document_id' => null, 'data' => null, 'note' => null])->save();

        $this->refresh($onboarding, $by);
    }

    /** Setzt den Abschluss, sobald alle Schritte erledigt sind, und nimmt ihn zurück, wenn wieder einer fehlt. */
    private function refresh(TeamOnboarding $onboarding, ?User $by): void
    {
        $onboarding->load('steps');
        $complete = $onboarding->steps->every(fn (TeamOnboardingStep $step): bool => $step->isDone());

        if ($complete && $onboarding->completed_at === null) {
            $onboarding->forceFill(['completed_at' => now()])->save();
            AuditLog::record(AuditEvent::TeamOnboardingCompleted, $by, $onboarding->user);
        } elseif (! $complete && $onboarding->completed_at !== null) {
            $onboarding->forceFill(['completed_at' => null])->save();
        }
    }
}
