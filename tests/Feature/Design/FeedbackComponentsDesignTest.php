<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeFeedbackComponentsDesignUser(): User
{
    return User::query()->create([
        'email' => 'design-feedback-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('ships notice and empty state component foundations', function () {
    $notices = file_get_contents(
        resource_path('css/vdbs/components/notices.css'),
    );
    $emptyStates = file_get_contents(
        resource_path('css/vdbs/components/empty-states.css'),
    );
    $noticeComponent = file_get_contents(
        resource_path('views/components/vdbs/notice.blade.php'),
    );
    $emptyStateComponent = file_get_contents(
        resource_path('views/components/vdbs/empty-state.blade.php'),
    );

    expect($notices)
        ->toContain('.notice__title')
        ->toContain('.notice__body')
        ->toContain('.notice__actions');

    expect($emptyStates)
        ->toContain('.empty-state__content')
        ->toContain('.empty-state__description')
        ->toContain('.empty-state__actions')
        ->toContain('.empty-state--compact');

    expect($noticeComponent)
        ->toContain("'info'")
        ->toContain("'success'")
        ->toContain("'warning'")
        ->toContain("'danger'");

    expect($emptyStateComponent)
        ->toContain("'title'")
        ->toContain("'description'")
        ->toContain("'headingLevel'")
        ->toContain('@isset($actions)');
});

it('documents notices and empty states in the design workbench', function () {
    $notices = file_get_contents(
        resource_path('views/design/pages/elemente/hinweise.blade.php'),
    );
    $emptyStates = file_get_contents(
        resource_path('views/design/pages/elemente/empty-states.blade.php'),
    );

    expect($notices)
        ->toContain('<x-vdbs.notice')
        ->toContain('role="status"')
        ->toContain('role="alert"');

    expect($emptyStates)
        ->toContain('<x-vdbs.empty-state')
        ->toContain('Mitglied hinzufügen')
        ->toContain('Filter zurücksetzen');
});

it('renders the empty state design page', function () {
    $user = makeFeedbackComponentsDesignUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/design/elemente/empty-states')
        ->assertOk()
        ->assertSeeText('Leere Zustände')
        ->assertSeeText('Noch keine Mitglieder vorhanden')
        ->assertSeeText('Keine passenden Ergebnisse');
});
