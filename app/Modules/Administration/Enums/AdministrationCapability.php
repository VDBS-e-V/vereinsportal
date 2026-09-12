<?php

namespace App\Modules\Administration\Enums;

enum AdministrationCapability: string
{
    case AdministrationAreaAccess = 'areas.administration.access';
    case BoardAreaAccess = 'areas.board.access';
    case CoordinationAreaAccess = 'areas.coordination.access';

    case PersonsRead = 'persons.read';
    case PersonsManage = 'persons.manage';

    case MembershipsRead = 'memberships.read';
    case MembershipsManage = 'memberships.manage';
    case MembershipDocumentsRead = 'membership_documents.read';
    case MembershipDocumentsManage = 'membership_documents.manage';
    case MembershipConsentsRead = 'membership_consents.read';
    case MembershipConsentsManage = 'membership_consents.manage';

    case PortalInvitationsManage = 'portal_invitations.manage';

    case UsersRead = 'users.read';
    case UserStatusManage = 'users.status.manage';
    case RolesManage = 'roles.manage';

    case CommunicationRead = 'communication.read';
    case CommunicationManage = 'communication.manage';

    case AuditRead = 'audit.read';
}
