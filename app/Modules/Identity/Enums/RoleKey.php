<?php

namespace App\Modules\Identity\Enums;

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
}