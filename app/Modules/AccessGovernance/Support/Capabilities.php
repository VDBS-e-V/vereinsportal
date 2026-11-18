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
        'administration' => [Capability::UsersView, Capability::UsersManage, Capability::RolesAssign, Capability::PeopleView, Capability::PeopleManage, Capability::AuditView, Capability::DesignView, Capability::SystemManage, Capability::OauthManage, Capability::TasksUse, Capability::TasksAdmin, Capability::ContactFormsManage, Capability::FaqManage, Capability::RoleLinksManage, Capability::TicketsHandle, Capability::NavigationManage],
        'administration_staff' => [Capability::UsersView, Capability::UsersManage, Capability::RolesAssign, Capability::PeopleView, Capability::PeopleManage, Capability::PartnersView, Capability::PartnersManage, Capability::TeamView, Capability::TeamManage, Capability::AccessRequestsView, Capability::AccessRequestsManage, Capability::ArticlesManage, Capability::PagesManage, Capability::EventsView, Capability::EventsManage, Capability::MailTemplatesManage, Capability::MailSegmentsManage, Capability::MailingsSend, Capability::ContactsManage, Capability::FormsView, Capability::FormsManage, Capability::TasksUse, Capability::ContactFormsManage, Capability::FaqManage, Capability::RoleLinksManage, Capability::TicketsHandle],
        'board_member' => [Capability::UsersView, Capability::UsersManage, Capability::RolesAssign, Capability::PeopleView, Capability::PeopleManage, Capability::AuditView, Capability::DesignView, Capability::MembershipsView, Capability::MembershipsManage, Capability::PartnersView, Capability::PartnersManage, Capability::TeamView, Capability::TeamManage, Capability::AccessRequestsView, Capability::AccessRequestsManage, Capability::ArticlesManage, Capability::PagesManage, Capability::EventsView, Capability::EventsManage, Capability::ReportsView, Capability::MailingsSend, Capability::MailingsMembers, Capability::MailingsApprove, Capability::MailTemplatesManage, Capability::MailSegmentsManage, Capability::ContactsManage, Capability::FormsView, Capability::FormsManage, Capability::FormsApprove, Capability::BoardMeetings, Capability::TasksUse, Capability::TasksAdmin],
        'coordination' => [Capability::PartnersView, Capability::PartnersManage, Capability::TeamView, Capability::TeamManage, Capability::EventsView, Capability::EventsManage, Capability::MailingsSendLimited, Capability::TasksUse],
        'team' => [Capability::TeamOnboardingOwn, Capability::FormsSubmitTeam],
        'member' => [Capability::MemberArea, Capability::FormsSubmitMember],
        'school' => [Capability::SchoolArea],
        'employee' => [Capability::EmployeeArea, Capability::FormsSubmit, Capability::TasksUse],
        'managing_director' => [Capability::EmployeeArea, Capability::FormsSubmit, Capability::FormsApprove, Capability::FormsView, Capability::TasksUse],
        'treasury' => [Capability::EmployeeArea, Capability::FormsSubmit, Capability::FormsManage, Capability::FormsView, Capability::TasksUse],
        'education_coordination' => [Capability::EducationView, Capability::EventsView, Capability::TasksUse],
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
        // Je Anwendungsinstanz (Anfrage, im Test je Test), Person und Sekunde nur einmal laden: Menü, Bereiche und Hinweise fragen die Rollen sonst dutzendfach ab.
        // Änderungen an Rollenzuordnungen leeren den Zwischenspeicher (RoleAssignment::booted).
        $cache = self::cache();
        $key = $user->getKey().'|'.now()->getTimestamp();

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $keys = $user->activeRoleAssignments()
            ->with('role:id,key')
            ->get()
            ->map(fn ($assignment): ?string => $assignment->role?->key)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $cache[$key] = $keys;
        self::$store[app()] = $cache;

        return $keys;
    }

    /** @var \WeakMap<object, array<string, list<string>>>|null */
    private static ?\WeakMap $store = null;

    /** @return array<string, list<string>> */
    private static function cache(): array
    {
        self::$store ??= new \WeakMap;

        return isset(self::$store[app()]) ? self::$store[app()] : [];
    }

    /** Leert die geladenen Rollen (nach Änderungen an Rollenzuordnungen). */
    public static function flush(): void
    {
        if (self::$store !== null) {
            self::$store[app()] = [];
        }
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
