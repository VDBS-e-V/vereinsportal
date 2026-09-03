<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\TwoFactorRecoveryCode;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\TotpService;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;

function makeSecurityPageUser(): User
{
    $user = User::query()->create([
        'email' => fake()
            ->unique()
            ->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('protects the security page from guests', function () {
    $this
        ->get(
            'http://my.vdb.test/profil/sicherheit'
        )
        ->assertRedirect(
            route('my.login')
        );
});

it('shows the security page for an authenticated user', function () {
    $user = makeSecurityPageUser();

    $this
        ->withSession([
            'identity.session_version' =>
                $user->session_version,
            'identity.account_validated_at' =>
                now()->timestamp,
        ])
        ->actingAs($user)
        ->get(
            'http://my.vdb.test/profil/sicherheit'
        )
        ->assertOk()
        ->assertSee(
            'Sicherheit und Zwei-Faktor-Authentifizierung'
        )
        ->assertSee(
            'E-Mail-2FA aktivieren'
        )
        ->assertSee(
            'TOTP einrichten'
        );
});

it('enables voluntary email two factor through the page', function () {
    $user = makeSecurityPageUser();

    $this->actingAs($user);

    Volt::test('identity.security')
        ->call('enableEmail')
        ->assertSet(
            'emailActive',
            true,
        )
        ->assertSet(
            'emailAvailable',
            true,
        );

    expect(
        TwoFactorMethod::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->where(
                'type',
                TwoFactorMethodType::Email,
            )
            ->whereNotNull('confirmed_at')
            ->whereNull('disabled_at')
            ->exists()
    )->toBeTrue();
});

it('shows mandatory two factor for an active board role', function () {
    $user = makeSecurityPageUser();

    $role = Role::query()->create([
        'key' => RoleKey::BoardMember,
        'name' => 'Vorstandsmitglied',
        'is_system' => true,
    ]);

    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' =>
            RoleAssignmentSource::Automatic,
        'starts_at' => now()->subMinute(),
    ]);

    $this->actingAs($user);

    Volt::test('identity.security')
        ->assertSet(
            'twoFactorRequired',
            true,
        )
        ->assertSet(
            'emailAvailable',
            true,
        )
        ->assertSee(
            'verpflichtend'
        );
});

it('prepares totp without activating the unconfirmed secret', function () {
    $user = makeSecurityPageUser();

    $this->actingAs($user);

    $component =
        Volt::test('identity.security')
            ->call('beginTotp');

    $methodId =
        $component->get(
            'totpMethodId'
        );

    $secret =
        $component->get(
            'totpSecret'
        );

    expect($methodId)
        ->not->toBeNull()
        ->and($secret)
        ->not->toBeNull();

    $method =
        TwoFactorMethod::query()
            ->findOrFail($methodId);

    expect($method->confirmed_at)
        ->toBeNull()
        ->and($method->secret)
        ->toBe($secret);

    /*
     * Roh in der Datenbank steht nicht
     * das Klartext-Secret.
     */
    $rawSecret = DB::table(
        'two_factor_methods'
    )
        ->where('id', $methodId)
        ->value('secret');

    expect($rawSecret)
        ->not->toBe($secret);
});

it('confirms totp and shows four recovery codes only in the current component', function () {
    $this->travelTo(
        now()->startOfSecond()
    );

    $user = makeSecurityPageUser();

    $this->actingAs($user);

    $component =
        Volt::test('identity.security')
            ->call('beginTotp');

    $methodId =
        $component->get(
            'totpMethodId'
        );

    $method =
        TwoFactorMethod::query()
            ->findOrFail($methodId);

    $totp = app(
        TotpService::class
    );

    $reflection =
        new ReflectionClass(
            TotpService::class
        );

    $codeMethod =
        $reflection->getMethod(
            'codeForCounter'
        );

    $codeMethod->setAccessible(
        true
    );

    $code = $codeMethod->invoke(
        $totp,
        $method->secret,
        intdiv(
            now()->timestamp,
            30,
        ),
    );

    $component
        ->set(
            'totpCode',
            $code,
        )
        ->call('confirmTotp')
        ->assertHasNoErrors()
        ->assertSet(
            'totpActive',
            true,
        );

    $recoveryCodes =
        $component->get(
            'recoveryCodes'
        );

    expect($recoveryCodes)
        ->toHaveCount(4)
        ->and(
            TwoFactorRecoveryCode::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->whereNull('used_at')
                ->whereNull(
                    'invalidated_at'
                )
                ->count()
        )
        ->toBe(4);

    /*
     * Neue Seiteninstanz darf die
     * Klartextcodes nicht rekonstruieren.
     */
    Volt::test('identity.security')
        ->assertSet(
            'recoveryCodes',
            [],
        );
});

it('regenerates recovery codes through the page', function () {
    $user = makeSecurityPageUser();

    TwoFactorMethod::query()->create([
        'user_id' => $user->id,
        'type' =>
            TwoFactorMethodType::Email,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user);

    $component =
        Volt::test('identity.security')
            ->call(
                'regenerateRecoveryCodes'
            );

    expect(
        $component->get(
            'recoveryCodes'
        )
    )->toHaveCount(4);
});