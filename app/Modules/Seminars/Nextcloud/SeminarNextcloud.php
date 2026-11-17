<?php

namespace App\Modules\Seminars\Nextcloud;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Models\Seminar;
use Carbon\Carbon;
use DomainException;
use Throwable;

/**
 * Nextcloud-Ordner der Seminare (docs/87, Phase 6): Ordner anlegen, Zugriff des Teams abgleichen, Ergebnislink erzeugen und verlängern. Fehler stehen am Seminar
 * und im Verlauf; ohne Anbindung blockiert nichts („Ordner manuell anlegen“).
 */
final class SeminarNextcloud
{
    public function __construct(private readonly NextcloudClient $client) {}

    public function enabled(): bool
    {
        return $this->client->enabled();
    }

    /** Legt die Ordner an und gleicht den Zugriff des bestätigten Teams ab. @throws Throwable */
    public function provision(Seminar $seminar, ?User $by = null): void
    {
        try {
            foreach (SeminarFolders::folders($seminar) as $folder) {
                $this->client->ensureFolder($folder);
            }

            $root = SeminarFolders::root($seminar);
            $this->client->ensureFolder($root);
            $this->client->grantUsers($root, $this->teamUids($seminar));
        } catch (Throwable $exception) {
            $this->failed($seminar, $exception, $by);

            throw $exception;
        }

        $seminar->forceFill(['nextcloud_folder_path' => SeminarFolders::root($seminar), 'nextcloud_status' => 'ok', 'nextcloud_error' => null, 'nextcloud_synced_at' => now()])->save();
        $this->event($seminar, $by, 'Nextcloud-Ordner angelegt und Zugriff des Teams abgeglichen ('.count($this->teamUids($seminar)).' Konten).');
    }

    /** Erzeugt den öffentlichen Ergebnislink (Standard zwei Jahre ab Seminarende, mindestens ein Jahr). @return array{url: string, expires: string} @throws DomainException|Throwable */
    public function createResultsLink(Seminar $seminar, ?string $expires, ?User $by = null): array
    {
        if ($seminar->nextcloud_folder_path === null) {
            throw new DomainException('Legen Sie zuerst den Nextcloud-Ordner an.');
        }

        $last = $seminar->sessions()->max('ends_at');
        $base = ($last !== null ? Carbon::parse($last) : now())->startOfDay();
        $expires ??= $base->copy()->addYears(max(1, (int) config('portal.seminars.retention.results_years', 2)))->toDateString();

        if (Carbon::parse($expires)->lt($base->copy()->addYear())) {
            throw new DomainException('Der Ergebnislink muss mindestens ein Jahr nach dem Seminar gelten.');
        }

        if ($seminar->results_share_id !== null) {
            $this->client->deleteShare($seminar->results_share_id);
        }

        $share = $this->client->createShare($seminar->nextcloud_folder_path.'/'.SeminarFolders::RESULTS, $expires);
        $seminar->forceFill(['results_share_id' => $share['id'], 'results_share_url' => $share['url'], 'results_expires_at' => $expires, 'results_expiry_notified_at' => null])->save();
        $this->event($seminar, $by, 'Ergebnislink in der Nextcloud erzeugt (gültig bis '.Carbon::parse($expires)->format('d.m.Y').').');

        return ['url' => $share['url'], 'expires' => $expires];
    }

    /** Gibt das neue Ablaufdatum an die Nextcloud weiter, wenn der Link dort erzeugt wurde. */
    public function extendResultsLink(Seminar $seminar, string $expires): void
    {
        if ($seminar->results_share_id !== null && $this->enabled()) {
            $this->client->setShareExpiry($seminar->results_share_id, $expires);
        }
    }

    /** Nextcloud-Kennungen des bestätigten Teams. Voraussetzung: Die Anmeldung über das Portal nutzt die feste Kennung des Kontos als Benutzername (user_oidc: use_unique_user_id aus). @return list<string> */
    public function teamUids(Seminar $seminar): array
    {
        return $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->whereNotNull('user_id')->with('user')->get()
            ->map(fn ($application): ?string => $application->user?->public_uuid ? (string) $application->user->public_uuid : null)->filter()->unique()->values()->all();
    }

    private function failed(Seminar $seminar, Throwable $exception, ?User $by): void
    {
        $seminar->forceFill(['nextcloud_status' => 'failed', 'nextcloud_error' => mb_substr($exception->getMessage(), 0, 500)])->save();
        $this->event($seminar, $by, 'Nextcloud-Ordner konnten nicht angelegt werden: '.mb_substr($exception->getMessage(), 0, 200));
        report($exception);
    }

    private function event(Seminar $seminar, ?User $by, string $comment): void
    {
        $seminar->events()->create(['type' => SeminarEventType::Note, 'occurred_at' => now(), 'actor_user_id' => $by?->id, 'actor_label' => $by !== null ? AuditLog::label($by) : 'System', 'comment' => $comment]);
    }
}
