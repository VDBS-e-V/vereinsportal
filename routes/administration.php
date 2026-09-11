<?php

use App\Modules\Administration\Http\Controllers\AssignUserRoleController;
use App\Modules\Administration\Http\Controllers\AuditEventIndexController;
use App\Modules\Administration\Http\Controllers\AuditEventShowController;
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
use App\Modules\Administration\Http\Controllers\ResendPortalInvitationController;
use App\Modules\Administration\Http\Controllers\RevokePortalInvitationController;
use App\Modules\Administration\Http\Controllers\StartPersonPortalInvitationController;
use App\Modules\Administration\Http\Controllers\StoreMembershipController;
use App\Modules\Administration\Http\Controllers\StorePersonController;
use App\Modules\Administration\Http\Controllers\UpdateEmailTemplateDraftController;
use App\Modules\Administration\Http\Controllers\UpdateEmailTemplateStatusController;
use App\Modules\Administration\Http\Controllers\UpdateMembershipController;
use App\Modules\Administration\Http\Controllers\UpdatePersonController;
use App\Modules\Administration\Http\Controllers\UpdateUserStatusController;
use App\Modules\Administration\Http\Controllers\UserIndexController;
use App\Modules\Administration\Http\Controllers\UserShowController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'web',
    'auth',
    'identity.revalidate',
    'administration.access',
])
    ->domain(config('domains.my'))
    ->prefix('verwaltung')
    ->name('administration.')
    ->group(function (): void {
        Route::get('/', HomeController::class)->name('home');

        Route::get('/personen', PersonIndexController::class)->name('persons.index');
        Route::get('/personen/anlegen', PersonCreateController::class)->name('persons.create');
        Route::post('/personen', StorePersonController::class)->name('persons.store');
        Route::get('/personen/{person}/mitgliedschaften/anlegen', MembershipCreateController::class)
            ->whereNumber('person')
            ->name('persons.memberships.create');
        Route::post('/personen/{person}/mitgliedschaften', StoreMembershipController::class)
            ->whereNumber('person')
            ->name('persons.memberships.store');
        Route::post('/personen/{person}/portal-einladung', StartPersonPortalInvitationController::class)
            ->whereNumber('person')
            ->name('persons.portal-invitations.store');
        Route::get('/personen/{person}', PersonShowController::class)
            ->whereNumber('person')
            ->name('persons.show');
        Route::get('/personen/{person}/bearbeiten', PersonEditController::class)
            ->whereNumber('person')
            ->name('persons.edit');
        Route::put('/personen/{person}', UpdatePersonController::class)
            ->whereNumber('person')
            ->name('persons.update');

        Route::post('/portal-einladungen/{portalInvitation}/erneut-senden', ResendPortalInvitationController::class)
            ->whereNumber('portalInvitation')
            ->name('portal-invitations.resend');
        Route::post('/portal-einladungen/{portalInvitation}/widerrufen', RevokePortalInvitationController::class)
            ->whereNumber('portalInvitation')
            ->name('portal-invitations.revoke');

        Route::get('/mitgliedschaften', MembershipIndexController::class)->name('memberships.index');
        Route::get('/mitgliedschaften/{membership}', MembershipShowController::class)
            ->whereNumber('membership')
            ->name('memberships.show');
        Route::get('/mitgliedschaften/{membership}/bearbeiten', MembershipEditController::class)
            ->whereNumber('membership')
            ->name('memberships.edit');
        Route::put('/mitgliedschaften/{membership}', UpdateMembershipController::class)
            ->whereNumber('membership')
            ->name('memberships.update');
        Route::get('/mitgliedschaften/{membership}/beenden', MembershipEndFormController::class)
            ->whereNumber('membership')
            ->name('memberships.end');
        Route::post('/mitgliedschaften/{membership}/beenden', EndMembershipController::class)
            ->whereNumber('membership')
            ->name('memberships.end.store');

        Route::get('/kommunikation/vorlagen', EmailTemplateIndexController::class)
            ->name('communication.templates.index');
        Route::get('/kommunikation/vorlagen/{emailTemplate}', EmailTemplateShowController::class)
            ->whereNumber('emailTemplate')
            ->name('communication.templates.show');
        Route::put('/kommunikation/vorlagen/{emailTemplate}/entwurf', UpdateEmailTemplateDraftController::class)
            ->whereNumber('emailTemplate')
            ->name('communication.templates.draft.update');
        Route::post('/kommunikation/vorlagen/{emailTemplate}/veroeffentlichen', PublishEmailTemplateController::class)
            ->whereNumber('emailTemplate')
            ->name('communication.templates.publish');
        Route::post('/kommunikation/vorlagen/{emailTemplate}/status', UpdateEmailTemplateStatusController::class)
            ->whereNumber('emailTemplate')
            ->name('communication.templates.status.update');
        Route::get('/kommunikation/versand', EmailDeliveryIndexController::class)
            ->name('communication.deliveries.index');
        Route::get('/kommunikation/versand/{emailDelivery}', EmailDeliveryShowController::class)
            ->whereNumber('emailDelivery')
            ->name('communication.deliveries.show');

        Route::get('/audit', AuditEventIndexController::class)
            ->name('audit.index');
        Route::get('/audit/{auditEvent}', AuditEventShowController::class)
            ->whereNumber('auditEvent')
            ->name('audit.show');

        Route::get('/benutzer', UserIndexController::class)->name('users.index');
        Route::get('/benutzer/{user}', UserShowController::class)
            ->whereNumber('user')
            ->name('users.show');
        Route::post('/benutzer/{user}/status', UpdateUserStatusController::class)
            ->whereNumber('user')
            ->name('users.status.update');
        Route::post('/benutzer/{user}/rollen', AssignUserRoleController::class)
            ->whereNumber('user')
            ->name('users.roles.assign');
        Route::post('/benutzer/{user}/rollen/{assignment}/beenden', EndUserRoleController::class)
            ->whereNumber('user')
            ->whereNumber('assignment')
            ->name('users.roles.end');
    });
