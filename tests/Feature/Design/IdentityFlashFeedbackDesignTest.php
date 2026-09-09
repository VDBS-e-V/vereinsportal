<?php

it('carries semantic flash types through signed identity workflows', function () {
    $confirmDeletion = file_get_contents(
        app_path('Modules/Identity/Http/Controllers/ConfirmAccountDeletionController.php'),
    );
    $withdrawDeletion = file_get_contents(
        app_path('Modules/Identity/Http/Controllers/WithdrawAccountDeletionController.php'),
    );
    $emailChange = file_get_contents(
        app_path('Modules/Identity/Http/Controllers/ConfirmEmailChangeController.php'),
    );
    $login = file_get_contents(
        resource_path('views/livewire/identity/login.blade.php'),
    );

    expect($confirmDeletion)
        ->toContain("'status_type'")
        ->toContain("'danger'")
        ->toContain("'warning'");

    expect($withdrawDeletion)
        ->toContain("'status_type'")
        ->toContain("'success'");

    expect($emailChange)
        ->toContain("'status_type'")
        ->toContain("'danger'")
        ->toContain("'success'");

    expect($login)
        ->toContain("session('status_type', 'success')")
        ->toContain(':type="$statusType"')
        ->toContain(':role="$statusRole"');
});
