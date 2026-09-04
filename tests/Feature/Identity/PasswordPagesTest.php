<?php

use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Database\Seeders\PasswordResetEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;

function preparePasswordResetTemplateForPageTest(): void
{
    test()->seed(
        PasswordResetEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'auth.password_reset')
        ->sole();

    $publisher = User::query()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Passwort zurücksetzen',
        'html' => <<<'HTML'
<p>
    <a href="{{ reset_url }}">
        Passwort zurücksetzen
    </a>
</p>
<p>Gültig bis {{ expires_at }}</p>
HTML,
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);
}

it('shows the forgot password page only on the my domain', function () {
    $this
        ->get(
            'http://my.vdb.test/passwort/vergessen'
        )
        ->assertOk()
        ->assertSee('Passwort vergessen');

    $this
        ->get(
            'http://verwaltung.vdb.test/passwort/vergessen'
        )
        ->assertNotFound();
});

it('returns the same public result for known and unknown emails', function () {
    Queue::fake();

    preparePasswordResetTemplateForPageTest();

    $user = User::query()->create([
        'email' => 'known@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $user->email_verified_at = now();
    $user->save();

    $known = Volt::test('identity.password-forgot')
        ->set(
            'email',
            'known@example.test',
        )
        ->call('requestReset')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSeeText(
            'Falls ein nutzbares Konto'
        )
        ->assertSeeText(
            'E-Mail-Adresse existiert'
        );

    $unknown = Volt::test('identity.password-forgot')
        ->set(
            'email',
            'unknown@example.test',
        )
        ->call('requestReset')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSeeText(
            'Falls ein nutzbares Konto'
        )
        ->assertSeeText(
            'E-Mail-Adresse existiert'
        );

    expect($known->get('submitted'))
        ->toBeTrue()
        ->and($unknown->get('submitted'))
        ->toBeTrue();
});

it('redirects guests from the password change page to login', function () {
    $this
        ->get(
            'http://my.vdb.test/profil/passwort'
        )
        ->assertRedirect(
            route('my.login')
        );

    $this->assertGuest();
});
