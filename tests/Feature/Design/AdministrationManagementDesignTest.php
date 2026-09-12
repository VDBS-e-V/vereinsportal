<?php

it('uses existing design system patterns for administration management', function () {
    $detail = file_get_contents(
        resource_path('views/administration/users/show.blade.php'),
    );
    $layout = file_get_contents(
        resource_path('views/layouts/administration.blade.php'),
    );

    expect($detail)
        ->toContain('<x-vdbs.validation-summary')
        ->toContain('<x-vdbs.danger-zone')
        ->toContain('class="form"')
        ->toContain('class="form__control"')
        ->toContain('btn btn--danger')
        ->toContain('@csrf')
        ->toContain("route('administration.users.status.update'")
        ->toContain("route('administration.users.roles.assign'")
        ->toContain("route('administration.users.roles.end'")
        ->not->toContain('LÖSCHEN');

    expect($layout)
        ->toContain('<x-vdbs.notice')
        ->toContain('session(')
        ->toContain("'status_type'")
        ->toContain("['danger', 'warning']");
});

it('keeps administration permissions capability based', function () {
    $access = file_get_contents(
        app_path('Modules/Administration/Support/AdministrationAccess.php'),
    );
    $statusController = file_get_contents(
        app_path('Modules/Administration/Http/Controllers/UpdateUserStatusController.php'),
    );
    $roleController = file_get_contents(
        app_path('Modules/Administration/Http/Controllers/AssignUserRoleController.php'),
    );
    $routes = file_get_contents(
        base_path('routes/administration.php'),
    );

    expect($access)
        ->toContain('ROLE_CAPABILITIES')
        ->toContain('public function allowsCapability')
        ->toContain('public function capabilities')
        ->not->toContain('public function canManage');

    expect($statusController)
        ->toContain('AdministrationCapability::UserStatusManage')
        ->toContain('allowsCapability')
        ->toContain('abort_unless');

    expect($roleController)
        ->toContain('AdministrationCapability::RolesManage')
        ->toContain('allowsCapability')
        ->toContain('abort_unless');

    expect($routes)
        ->toContain('administration.capability:')
        ->toContain('AdministrationCapability::AuditRead')
        ->toContain('AdministrationCapability::CommunicationManage')
        ->toContain('AdministrationCapability::MembershipDocumentsManage');
});
