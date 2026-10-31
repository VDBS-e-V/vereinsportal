<?php

namespace App\Modules\Events\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Events\Enums\EventStatus;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventSession;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Str;

/** Großveranstaltungen anlegen, ändern, löschen und Termine pflegen. Das Protokoll nennt nur Titel und Feldnamen, nie Teilnehmende. */
final class EventAdministration
{
    /** @param  array<string, mixed>  $data */
    public function create(array $data, User $by): Event
    {
        $event = Event::query()->create($this->columns($data) + ['slug' => $this->uniqueSlug((string) $data['title']), 'created_by_user_id' => $by->id, 'updated_by_user_id' => $by->id]);

        AuditLog::record(AuditEvent::EventCreated, $by, null, ['titel' => $event->title]);

        return $event;
    }

    /** @param  array<string, mixed>  $data */
    public function update(Event $event, array $data, User $by): Event
    {
        $event->fill($this->columns($data) + ['updated_by_user_id' => $by->id])->save();

        $changed = array_values(array_diff(array_keys($event->getChanges()), ['updated_at', 'updated_by_user_id']));

        if ($changed !== []) {
            AuditLog::record(AuditEvent::EventUpdated, $by, null, ['titel' => $event->title, 'felder' => $changed]);
        }

        return $event;
    }

    /** @throws DomainException wenn es schon Anmeldungen gibt */
    public function delete(Event $event, User $by): void
    {
        if ($event->registrations()->exists()) {
            throw new DomainException('Die Veranstaltung hat Anmeldungen und lässt sich nicht löschen. Schließen Sie sie stattdessen.');
        }

        AuditLog::record(AuditEvent::EventDeleted, $by, null, ['titel' => $event->title]);

        $event->delete();
    }

    /** @param  array{date: string, start: string, end: string, location?: ?string, notes?: ?string}  $data */
    public function addSession(Event $event, array $data): EventSession
    {
        return $event->sessions()->create([
            'starts_at' => Carbon::parse($data['date'].' '.$data['start']),
            'ends_at' => Carbon::parse($data['date'].' '.$data['end']),
            'location' => filled($data['location'] ?? null) ? trim($data['location']) : null,
            'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function columns(array $data): array
    {
        $clean = static fn (mixed $value): mixed => is_string($value) ? (trim($value) === '' ? null : trim($value)) : $value;

        return [
            'title' => trim((string) $data['title']),
            'description' => $clean($data['description'] ?? null),
            'location' => $clean($data['location'] ?? null),
            'status' => EventStatus::from((string) $data['status']),
            'capacity' => ($data['capacity'] ?? null) !== null && $data['capacity'] !== '' ? (int) $data['capacity'] : null,
            'registration_deadline' => filled($data['registration_deadline'] ?? null) ? Carbon::parse($data['registration_deadline']) : null,
        ];
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title) ?: 'veranstaltung', 70, '');
        $slug = $base;

        while (Event::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
