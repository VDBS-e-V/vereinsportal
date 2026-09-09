<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeDesignWorkbenchUser(): User
{
    $user = User::query()->create([
        'email' => 'design-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

function designWorkbenchSession(
    User $user,
): array {
    return [
        'identity.session_version' => $user->session_version,
        'identity.account_validated_at' => now()->timestamp,
    ];
}

it('protects the design workbench from guests', function () {
    $this
        ->get('http://my.vdb.test/design')
        ->assertRedirect(
            route('my.login')
        );
});

it('shows the design workbench and its foundation pages', function () {
    $user = makeDesignWorkbenchUser();

    $this
        ->withSession(
            designWorkbenchSession($user)
        )
        ->actingAs($user)
        ->get('http://my.vdb.test/design')
        ->assertOk()
        ->assertSeeText('Vorlagen & Elemente')
        ->assertSee('Grundlagen')
        ->assertSee('Elemente')
        ->assertSee('Vorlagen');

    foreach ([
        'grundlagen',
        'elemente',
        'vorlagen',
    ] as $page) {
        $this
            ->withSession(
                designWorkbenchSession($user)
            )
            ->actingAs($user)
            ->get('http://my.vdb.test/design/'.$page)
            ->assertOk();
    }
});
