<?php

use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Http\Controllers\AssignUserRoleController;
use App\Modules\Administration\Http\Controllers\AuditEventIndexController;
use App\Modules\Administration\Http\Controllers\AuditEventShowController;
use App\Modules\Administration\Http\Controllers\BoardHomeController;
use App\Modules\Administration\Http\Controllers\CoordinationHomeController;
use App\Modules\Administration\Http\Controllers\DownloadMembershipDocumentController;
use App\Modules\Administration\Http\Controllers\EmailDeliveryIndexController;
use App\Modules\Administration\Http\Controllers\EmailDeliveryShowController;
use App\Modules\Administration\Http\Controllers\EmailTemplateIndexController;
use App\Modules\Administration\Http\Controllers\EmailTemplateShowController;
use App\Modules\Administration\Http\Controllers\EndMembershipController;
use App\Modules\Administration\Http\Controllers\EndUserRoleController;
use App\Modules\Administration\Http\Controllers\HomeController;
use App\Modules\Administration\Http\Controllers\MembershipCreateController;
use App\Modules\Administration\Http\Controllers\MembershipEditController;
use App\Modules\Administration\Http\Controllers\MembershipEndFormController;
use App\Modules\Administration\Http\Controllers\MembershipIndexController;
use App\Modules\Administration\Http\Controllers\MembershipShowController;
use App\Modules\Administration\Http\Controllers\PersonCreateController;
use App\Modules\Administration\Http\Controllers\PersonEditController;
use App\Modules\Administration\Http\Controllers\PersonIndexController;
use App\Modules\Administration\Http\Controllers\PersonShowController;
use App\Modules\Administration\Http\Controllers\PublishEmailTemplateController;
use App\Modules\Administration\Http\Controllers\ReplaceMembershipDocumentController;
use App\Modules\Administration\Http\Controllers\ResendPortalInvitationController;
use App\Modules\Administration\Http\Controllers\RevokeMembershipConsentController;
use App\Modules\Administration\Http\Controllers\RevokePortalInvitationController;
use App\Modules\Administration\Http\Controllers\StartPersonPortalInvitationController;
use App\Modules\Administration\Http\Controllers\StoreMembershipConsentController;
use App\Modules\Administration\Http\Controllers\StoreMembershipController;
use App\Modules\Administration\Http\Controllers\StoreMembershipDocumentController;
use App\Modules\Administration\Http\Controllers\StorePersonController;
use App\Modules\Administration\Http\Controllers\UpdateEmailTemplateDraftController;
use App\Modules\Administration\Http\Controllers\UpdateEmailTemplateStatusController;
use App\Modules\Administration\Http\Controllers\UpdateMembershipController;
use App\Modules\Administration\Http\Controllers\UpdatePersonController;
use App\Modules\Administration\Http\Controllers\UpdateUserStatusController;
use App\Modules\Administration\Http\Controllers\UserIndexController;
use App\Modules\Administration\Http\Controllers\UserShowController;
use Illuminate\Support\Facades\Route;

$requires = static fn (AdministrationCapability $capability): string => 'administration.capability:'.$capability->value;

$staffMiddleware = [
    'web',
    'auth',
    'identity.revalidate',
    'administration.access',
];

Route::middleware([
    ...$staffMiddleware,
    $requires(AdministrationCapability::AdministrationAreaAccess),
])
    ->domain(config('domains.my'))
    ->prefix('verwaltung')
    ->name('administration.')
    ->group(function () use ($requires): void {
        Route::get('/', HomeController::class)->name('home');

        Route::get('/personen', PersonIndexController::class)
            ->middleware($requires(AdministrationCapability::PersonsRead))
            ->name('persons.index');
        Route::get('/personen/anlegen', PersonCreateController::class)
            ->middleware($requires(AdministrationCapability::PersonsManage))
            ->name('persons.create');
        Route::post('/personen', StorePersonController::class)
            ->middleware($requires(AdministrationCapability::PersonsManage))
            ->name('persons.store');
        Route::post('/personen/{person}/portal-einladung', StartPersonPortalInvitationController::class)
            ->middleware($requires(AdministrationCapability::PortalInvitationsManage))
            ->whereNumber('person')
            ->name('persons.portal-invitations.store');
        Route::get('/personen/{person}', PersonShowController::class)
            ->middleware($requires(AdministrationCapability::PersonsRead))
            ->whereNumber('person')
            ->name('persons.show');
        Route::get('/personen/{person}/bearbeiten', PersonEditController::class)
            ->middleware($requires(AdministrationCapability::PersonsManage))
            ->whereNumber('person')
            ->name('persons.edit');
        Route::put('/personen/{person}', UpdatePersonController::class)
            ->middleware($requires(AdministrationCapability::PersonsManage))
            ->whereNumber('person')
            ->name('persons.update');

        Route::post('/portal-einladungen/{portalInvitation}/erneut-senden', ResendPortalInvitationController::class)
            ->middleware($requires(AdministrationCapability::PortalInvitationsManage))
            ->whereNumber('portalInvitation')
            ->name('portal-invitations.resend');
        Route::post('/portal-einladungen/{portalInvitation}/widerrufen', RevokePortalInvitationController::class)
            ->middleware($requires(AdministrationCapability::PortalInvitationsManage))
            ->whereNumber('portalInvitation')
            ->name('portal-invitations.revoke');

        Route::get('/kommunikation/vorlagen', EmailTemplateIndexController::class)
            ->middleware($requires(AdministrationCapability::CommunicationRead))
            ->name('communication.templates.index');
        Route::get('/kommunikation/vorlagen/{emailTemplate}', EmailTemplateShowController::class)
            ->middleware($requires(AdministrationCapability::CommunicationRead))
            ->whereNumber('emailTemplate')
            ->name('communication.templates.show');
        Route::put('/kommunikation/vorlagen/{emailTemplate}/entwurf', UpdateEmailTemplateDraftController::class)
            ->middleware($requires(AdministrationCapability::CommunicationManage))
            ->whereNumber('emailTemplate')
            ->name('communication.templates.draft.update');
        Route::post('/kommunikation/vorlagen/{emailTemplate}/veroeffentlichen', PublishEmailTemplateController::class)
            ->middleware($requires(AdministrationCapability::CommunicationManage))
            ->whereNumber('emailTemplate')
            ->name('communication.templates.publish');
        Route::post('/kommunikation/vorlagen/{emailTemplate}/status', UpdateEmailTemplateStatusController::class)
            ->middleware($requires(AdministrationCapability::CommunicationManage))
            ->whereNumber('emailTemplate')
            ->name('communication.templates.status.update');
        Route::get('/kommunikation/versand', EmailDeliveryIndexController::class)
            ->middleware($requires(AdministrationCapability::CommunicationRead))
            ->name('communication.deliveries.index');
        Route::get('/kommunikation/versand/{emailDelivery}', EmailDeliveryShowController::class)
            ->middleware($requires(AdministrationCapability::CommunicationRead))
            ->whereNumber('emailDelivery')
            ->name('communication.deliveries.show');

        Route::get('/audit', AuditEventIndexController::class)
            ->middleware($requires(AdministrationCapability::AuditRead))
            ->name('audit.index');
        Route::get('/audit/{auditEvent}', AuditEventShowController::class)
            ->middleware($requires(AdministrationCapability::AuditRead))
            ->whereNumber('auditEvent')
            ->name('audit.show');

        Route::get('/benutzer', UserIndexController::class)
            ->middleware($requires(AdministrationCapability::UsersRead))
            ->name('users.index');
        Route::get('/benutzer/{user}', UserShowController::class)
            ->middleware($requires(AdministrationCapability::UsersRead))
            ->whereNumber('user')
            ->name('users.show');
        Route::post('/benutzer/{user}/status', UpdateUserStatusController::class)
            ->middleware($requires(AdministrationCapability::UserStatusManage))
            ->whereNumber('user')
            ->name('users.status.update');
        Route::post('/benutzer/{user}/rollen', AssignUserRoleController::class)
            ->middleware($requires(AdministrationCapability::RolesManage))
            ->whereNumber('user')
            ->name('users.roles.assign');
        Route::post('/benutzer/{user}/rollen/{assignment}/beenden', EndUserRoleController::class)
            ->middleware($requires(AdministrationCapability::RolesManage))
            ->whereNumber('user')
            ->whereNumber('assignment')
            ->name('users.roles.end');
    });

Route::middleware([
    ...$staffMiddleware,
    $requires(AdministrationCapability::BoardAreaAccess),
])
    ->domain(config('domains.my'))
    ->prefix('vorstand')
    ->group(function () use ($requires): void {
        Route::get('/', BoardHomeController::class)->name('board.home');

        Route::get('/personen/{person}/mitgliedschaften/anlegen', MembershipCreateController::class)
            ->middleware($requires(AdministrationCapability::MembershipsManage))
            ->whereNumber('person')
            ->name('administration.persons.memberships.create');
        Route::post('/personen/{person}/mitgliedschaften', StoreMembershipController::class)
            ->middleware($requires(AdministrationCapability::MembershipsManage))
            ->whereNumber('person')
            ->name('administration.persons.memberships.store');

        Route::get('/mitgliedschaften', MembershipIndexController::class)
            ->middleware($requires(AdministrationCapability::MembershipsRead))
            ->name('administration.memberships.index');
        Route::get('/mitgliedschaften/{membership}', MembershipShowController::class)
            ->middleware($requires(AdministrationCapability::MembershipsRead))
            ->whereNumber('membership')
            ->name('administration.memberships.show');
        Route::get('/mitgliedschaften/{membership}/bearbeiten', MembershipEditController::class)
            ->middleware($requires(AdministrationCapability::MembershipsManage))
            ->whereNumber('membership')
            ->name('administration.memberships.edit');
        Route::put('/mitgliedschaften/{membership}', UpdateMembershipController::class)
            ->middleware($requires(AdministrationCapability::MembershipsManage))
            ->whereNumber('membership')
            ->name('administration.memberships.update');
        Route::get('/mitgliedschaften/{membership}/beenden', MembershipEndFormController::class)
            ->middleware($requires(AdministrationCapability::MembershipsManage))
            ->whereNumber('membership')
            ->name('administration.memberships.end');
        Route::post('/mitgliedschaften/{membership}/beenden', EndMembershipController::class)
            ->middleware($requires(AdministrationCapability::MembershipsManage))
            ->whereNumber('membership')
            ->name('administration.memberships.end.store');
        Route::post('/mitgliedschaften/{membership}/dokumente', StoreMembershipDocumentController::class)
            ->middleware($requires(AdministrationCapability::MembershipDocumentsManage))
            ->whereNumber('membership')
            ->name('administration.memberships.documents.store');
        Route::get('/mitgliedschaften/{membership}/dokumente/{membershipDocument}', DownloadMembershipDocumentController::class)
            ->middleware($requires(AdministrationCapability::MembershipDocumentsRead))
            ->whereNumber('membership')
            ->whereNumber('membershipDocument')
            ->name('administration.memberships.documents.download');
        Route::post('/mitgliedschaften/{membership}/dokumente/{membershipDocument}/ersetzen', ReplaceMembershipDocumentController::class)
            ->middleware($requires(AdministrationCapability::MembershipDocumentsManage))
            ->whereNumber('membership')
            ->whereNumber('membershipDocument')
            ->name('administration.memberships.documents.replace');
        Route::post('/mitgliedschaften/{membership}/zustimmungen', StoreMembershipConsentController::class)
            ->middleware($requires(AdministrationCapability::MembershipConsentsManage))
            ->whereNumber('membership')
            ->name('administration.memberships.consents.store');
        Route::post('/mitgliedschaften/{membership}/zustimmungen/{membershipConsent}/widerrufen', RevokeMembershipConsentController::class)
            ->middleware($requires(AdministrationCapability::MembershipConsentsManage))
            ->whereNumber('membership')
            ->whereNumber('membershipConsent')
            ->name('administration.memberships.consents.revoke');
    });

Route::middleware([
    ...$staffMiddleware,
    $requires(AdministrationCapability::CoordinationAreaAccess),
])
    ->domain(config('domains.my'))
    ->prefix('koordination')
    ->group(function (): void {
        Route::get('/', CoordinationHomeController::class)->name('coordination.home');
    });
