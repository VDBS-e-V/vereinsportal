<?php

use App\Support\BotGuard\BotGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    config(['portal.bot_guard.enabled' => true, 'portal.bot_guard.min_seconds' => 0, 'portal.bot_guard.difficulty_bits' => 8]);
});

/** Löst die Rechenaufgabe eines Tokens wie der Browser. */
function botProof(string $token, int $bits = 8): string
{
    $guard = app(BotGuard::class);

    for ($n = 0; ; $n++) {
        if ($guard->proofValid($token, (string) $n, $bits)) {
            return (string) $n;
        }
    }
}

function botPost(array $overrides = [], ?string $token = null)
{
    $token ??= app(BotGuard::class)->issue();

    return test()->from('/')->post(route('password.email'), array_merge([
        'email' => 'jemand@example.org',
        BotGuard::TOKEN_FIELD => $token,
        BotGuard::PROOF_FIELD => botProof($token),
    ], $overrides));
}

it('renders token, honeypot and proof field in public forms only when enabled', function () {
    $html = $this->get(route('portal.contact'))->assertOk()->getContent();

    expect($html)->toContain('data-vdbs-bot-guard')->toContain('name="_bg_token"')->toContain('name="homepage_url"')->toContain('name="_bg_proof"');

    config(['portal.bot_guard.enabled' => false]);

    $this->get(route('portal.contact'))->assertDontSee('data-vdbs-bot-guard', false);
});

it('puts the guard into every public form', function () {
    foreach (['portal.contact', 'portal.access', 'join.apply', 'login', 'password.request'] as $name) {
        $this->get(route($name))->assertOk()->assertSee('data-vdbs-bot-guard', false);
    }
});

it('accepts a form with a valid token and solved proof', function () {
    botPost()->assertSessionMissing('bot_guard_failed');
});

it('rejects a form without token', function () {
    $this->from('/')->post(route('password.email'), ['email' => 'jemand@example.org'])
        ->assertRedirect('/')->assertSessionHas('bot_guard_failed');
});

it('rejects a filled honeypot', function () {
    botPost([BotGuard::HONEYPOT_FIELD => 'https://spam.example'])->assertSessionHas('bot_guard_failed');
});

it('rejects a tampered token', function () {
    $token = app(BotGuard::class)->issue();

    botPost([], $token.'x')->assertSessionHas('bot_guard_failed');
});

it('rejects a wrong proof of work', function () {
    config(['portal.bot_guard.difficulty_bits' => 20]);

    $token = app(BotGuard::class)->issue();
    $wrong = null;

    for ($n = 0; $n < 50; $n++) {
        if (! app(BotGuard::class)->proofValid($token, (string) $n, 20)) {
            $wrong = (string) $n;
            break;
        }
    }

    botPost([BotGuard::PROOF_FIELD => $wrong], $token)->assertSessionHas('bot_guard_failed');
    botPost([BotGuard::PROOF_FIELD => ''], app(BotGuard::class)->issue())->assertSessionHas('bot_guard_failed');
});

it('rejects forms that are sent too fast', function () {
    config(['portal.bot_guard.min_seconds' => 60]);

    botPost()->assertSessionHas('bot_guard_failed');
});

it('rejects expired tokens', function () {
    config(['portal.bot_guard.max_seconds' => -1]);

    botPost()->assertSessionHas('bot_guard_failed');
});

it('accepts each token only once', function () {
    $token = app(BotGuard::class)->issue();

    botPost([], $token)->assertSessionMissing('bot_guard_failed');
    botPost([], $token)->assertSessionHas('bot_guard_failed');
});

it('keeps the input and shows a notice after a rejection', function () {
    $this->from(route('password.request'))->post(route('password.email'), ['email' => 'jemand@example.org'])->assertRedirect(route('password.request'));

    $this->get(route('password.request'))->assertSee('Prüfung nicht bestanden')->assertSee('jemand@example.org', false);
});

it('does not check safe requests and lets everything pass when disabled', function () {
    $this->get(route('portal.contact'))->assertOk();

    config(['portal.bot_guard.enabled' => false]);

    $this->from('/')->post(route('password.email'), ['email' => 'jemand@example.org'])->assertSessionMissing('bot_guard_failed');
});

it('counts leading zero bits', function () {
    $guard = app(BotGuard::class);

    expect($guard->leadingZeroBits("\x00\x00\x80"))->toBe(16)
        ->and($guard->leadingZeroBits("\x00\x1f"))->toBe(11)
        ->and($guard->leadingZeroBits("\xff"))->toBe(0);
});
