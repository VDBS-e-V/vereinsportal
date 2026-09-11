<?php

namespace App\Modules\Administration\Support;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Models\Membership;

final class AuditEventPresentation
{
    public static function actorLabel(AuditEvent $event): string
    {
        if ($event->actor_type === AuditActorType::System) {
            return 'System';
        }

        if ($event->actor_type === AuditActorType::Console) {
            return 'Konsole';
        }

        $actor = $event->actor;

        if (! $actor instanceof User) {
            return 'Benutzerkonto nicht mehr verfügbar';
        }

        $person = $actor->person;
        $name = $person instanceof Person
            ? trim($person->first_name.' '.$person->last_name)
            : '';

        return $name !== ''
            ? $name.' · '.$actor->email
            : $actor->email;
    }

    public static function subjectLabel(AuditEvent $event): string
    {
        if ($event->subject_type === null || $event->subject_id === null) {
            return 'Kein Fachobjekt';
        }

        $label = match ($event->subject_type) {
            'person' => 'Person',
            'user' => 'Benutzerkonto',
            'membership' => 'Mitgliedschaft',
            'role_assignment' => 'Rollenzuweisung',
            'portal_invitation' => 'Portal-Einladung',
            'email_template' => 'E-Mail-Vorlage',
            'email_template_version' => 'E-Mail-Vorlagenversion',
            default => $event->subject_type,
        };

        return $label.' #'.$event->subject_id;
    }

    public static function subjectUrl(AuditEvent $event): ?string
    {
        if ($event->subject_id === null) {
            return null;
        }

        if ($event->subject_type === 'portal_invitation') {
            $invitation = PortalInvitation::query()->find($event->subject_id);

            return $invitation instanceof PortalInvitation
                ? route('administration.persons.show', $invitation->person_id)
                : null;
        }

        if ($event->subject_type === 'email_template_version') {
            $version = EmailTemplateVersion::query()->find($event->subject_id);

            return $version instanceof EmailTemplateVersion
                ? route(
                    'administration.communication.templates.show',
                    $version->email_template_id,
                )
                : null;
        }

        return match ($event->subject_type) {
            'person' => Person::query()->whereKey($event->subject_id)->exists()
                ? route('administration.persons.show', $event->subject_id)
                : null,
            'user' => User::query()->whereKey($event->subject_id)->exists()
                ? route('administration.users.show', $event->subject_id)
                : null,
            'membership' => Membership::query()->whereKey($event->subject_id)->exists()
                ? route('administration.memberships.show', $event->subject_id)
                : null,
            'email_template' => EmailTemplate::query()->whereKey($event->subject_id)->exists()
                ? route('administration.communication.templates.show', $event->subject_id)
                : null,
            default => null,
        };
    }

    public static function value(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Ja' : 'Nein';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return $encoded !== false ? $encoded : '—';
    }
}
