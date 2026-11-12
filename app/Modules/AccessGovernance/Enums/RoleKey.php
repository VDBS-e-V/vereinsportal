<?php

namespace App\Modules\AccessGovernance\Enums;

enum RoleKey: string
{
    case Guest = 'guest';
    case Member = 'member';
    case BoardMember = 'board_member';
    case Team = 'team';
    case AdministrationStaff = 'administration_staff';
    case EducationCoordination = 'education_coordination';
    case Coordination = 'coordination';
    case Administration = 'administration';
    case School = 'school';
    case Employee = 'employee';
    case ManagingDirector = 'managing_director';
    case Treasury = 'treasury';

    public function label(): string
    {
        return match ($this) {
            self::Guest => 'Gast',
            self::Member => 'Vereinsmitglied',
            self::BoardMember => 'Vorstandsmitglied',
            self::Team => 'Teamende',
            self::AdministrationStaff => 'Geschäftsstelle',
            self::EducationCoordination => 'Bildungsreferenten',
            self::Coordination => 'Koordination',
            self::Administration => 'Administration',
            self::School => 'Schule',
            self::Employee => 'Mitarbeitende',
            self::ManagingDirector => 'Geschäftsführung',
            self::Treasury => 'Kassenführung',
        };
    }
}
