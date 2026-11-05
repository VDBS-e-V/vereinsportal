<?php

namespace App\Modules\AccessGovernance\Support;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Enums\RoleKey;

/**
 * Zuordnung Rolle -> Berechtigungen. Eine Berechtigung gilt, wenn der Nutzer mindestens eine zurzeit gültige Rolle hat,
 * die sie enthält. Neue Berechtigungen und Rollen werden hier an einer Stelle gepflegt.
 */
final class Capabilities
{
    /** @var array<string, list<Capability>> */
    private const MAP = [
        'administration' => [Capability::UsersView, Capability::UsersManage, Capability::RolesAssign, Capability::PeopleView, Capability::PeopleManage, Capability::AuditView, Capability::DesignView, Capability::SystemManage, Capability::OauthManage],
        'administration_staff' => [Capability::UsersView, Capability::UsersManage, Capability::RolesAssign, Capability::PeopleView, Capability::PeopleManage, Capability::PartnersView, Capability::PartnersManage, Capability::SeminarsView, Capability::SeminarsManage, Capability::TeamView, Capability::TeamManage, Capability::AccessRequestsView, Capability::AccessRequestsManage, Capability::ArticlesManage, Capability::PagesManage, Capability::EventsView, Capability::EventsManage, Capability::MailTemplatesManage, Capability::MailingsSend, Capability::ContactsManage, Capability::SeminarsDocuments, Capability::FormsSubmit, Capability::FormsView, Capability::FormsManage],
        'board_member' => [Capability::UsersView, Capability::UsersManage, Capability::RolesAssign, Capability::PeopleView, Capability::PeopleManage, Capability::AuditView, Capability::DesignView, Capability::MembershipsView, Capability::MembershipsManage, Capability::PartnersView, Capability::PartnersManage, Capability::SeminarsView, Capability::SeminarsManage, Capability::SeminarPricesManage, Capability::TeamView, Capability::TeamManage, Capability::AccessRequestsView, Capability::AccessRequestsManage, Capability::ArticlesManage, Capability::PagesManage, Capability::EventsView, Capability::EventsManage, Capability::ReportsView, Capability::MailingsSend, Capability::MailingsMembers, Capability::MailingsApprove, Capability::MailTemplatesManage, Capability::ContactsManage, Capability::SeminarsDocuments, Capability::SeminarsReopen, Capability::FormsSubmit, Capability::FormsView, Capability::FormsManage, Capability::FormsApprove],
        'coordination' => [Capability::PartnersView, Capability::PartnersManage, Capability::SeminarsView, Capability::SeminarsManage, Capability::SeminarPricesManage, Capability::TeamView, Capability::TeamManage, Capability::EventsView, Capability::EventsManage, Capability::MailingsTeam, Capability::SeminarsDocuments, Capability::FeedbackManage, Capability::FeedbackView, Capability::FormsSubmit],
        'team' => [Capability::TeamOnboardingOwn, Capability::SeminarsApply, Capability::FormsSubmit],
        'member' => [Capability::MemberArea],
        'school' => [Capability::SchoolArea],
        'employee' => [Capability::EmployeeArea, Capability::FormsSubmit],
        'managing_director' => [Capability::EmployeeArea, Capability::FormsSubmit, Capability::FormsApprove, Capability::FormsView],
        'treasury' => [Capability::EmployeeArea, Capability::FormsSubmit, Capability::FormsManage, Capability::FormsView],
        'education_coordination' => [Capability::FormsSubmit, Capability::EducationView, Capability::SeminarsView, Capability::EventsView],
    ];

    /** Rollen, die nur der Vorstand vergeben und beenden darf. @var list<RoleKey> */
    public const BOARD_ONLY = [RoleKey::ManagingDirector, RoleKey::Treasury];

    /** Schlüssel der Rollen, die eine Berechtigung enthalten (für Benachrichtigungen). @return list<string> */
    public static function roleKeysWith(Capability $capability): array
    {
        return array_keys(array_filter(self::MAP, fn (array $caps): bool => in_array($capability, $caps, true)));
    }

    public static function has(User $user, Capability $capability): bool
    {
        foreach (self::activeRoleKeys($user) as $key) {
            if (in_array($capability, self::MAP[$key] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    /** Rollen, die der Nutzer jetzt hat (Beginn erreicht, Ende nicht überschritten). @return list<string> */
    public static function activeRoleKeys(User $user): array
    {
        return $user->activeRoleAssignments()
            ->with('role:id,key')
            ->get()
            ->map(fn ($assignment): ?string => $assignment->role?->key)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Darf der Nutzer die Rolle vergeben? Administration vergibt nur Administration; Geschäftsführung und Kassenführung nur der Vorstand. */
    public static function canAssign(User $user, RoleKey $role): bool
    {
        if (! self::has($user, Capability::RolesAssign)) {
            return false;
        }

        if ($role === RoleKey::Administration) {
            return in_array(RoleKey::Administration->value, self::activeRoleKeys($user), true);
        }

        // Geschäftsführung und Kassenführung benennt und entzieht nur der Vorstand.
        if (in_array($role, self::BOARD_ONLY, true)) {
            return in_array(RoleKey::BoardMember->value, self::activeRoleKeys($user), true);
        }

        return true;
    }
}
