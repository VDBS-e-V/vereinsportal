<?php

use App\Modules\Access\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

it('shows the login form to guests', function () {
    $this->get('/login')->assertOk()->assertSee('Anmelden');
});

it('redirects guests from the start page to the login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('logs in an active user and records the login time', function () {
    $user = User::factory()->create(['email' => 'erika@example.org']);

    $this->post('/login', ['email' => 'erika@example.org', 'password' => 'password'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects a wrong password with a generic message', function () {
    User::factory()->create(['email' => 'erika@example.org']);

    $this->from('/login')
        ->post('/login', ['email' => 'erika@example.org', 'password' => 'falsch'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('does not reveal whether an account exists', function () {
    $message = 'Die Anmeldedaten sind nicht korrekt oder das Konto ist nicht aktiv.';

    $this->post('/login', ['email' => 'niemand@example.org', 'password' => 'x'])
        ->assertSessionHasErrors(['email' => $message]);

    User::factory()->create(['email' => 'erika@example.org']);

    $this->post('/login', ['email' => 'erika@example.org', 'password' => 'x'])
        ->assertSessionHasErrors(['email' => $message]);
});

it('refuses users that are not active', function () {
    User::factory()->unverified()->create(['email' => 'neu@example.org']);

    $this->post('/login', ['email' => 'neu@example.org', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('locks out after too many failed attempts', function () {
    User::factory()->create(['email' => 'erika@example.org']);

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => 'erika@example.org', 'password' => 'falsch']);
    }

    $this->post('/login', ['email' => 'erika@example.org', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out and invalidates the session', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('shows the start page to authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('Willkommen')
        ->assertSee('Abmelden');
});

it('shows header and footer navigation depending on the login state', function () {
    $this->get('/login')->assertSee('Anmelden')->assertDontSee('Design-Bibliothek');

    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertSee('Design-Bibliothek')
        ->assertSee('Abmelden');
});

it('shows the area placeholders and a login button to guests on public pages', function () {
    $this->get('/design')->assertRedirect(route('login'));

    $response = $this->get('/login');
    $response->assertSee('Vorstand')->assertSee('Geschäftsstelle');
});

it('renders the configured footer columns', function () {
    $this->get('/login')
        ->assertSee('Informationen für')
        ->assertSee('Service-Portal')
        ->assertSee('Soziale Medien')
        ->assertSee('Impressum');
});
