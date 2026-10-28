<?php

use App\Modules\Access\Models\User;

beforeEach(fn () => $this->withoutVite());

it('renders the start page for guests with the structure of the design system', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('vdbs-hero--compact')
        ->toContain('vdbs-main--full')
        ->toContain('Schnellzugriff')
        ->toContain('Die Bereiche des Portals')
        ->toContain('Zugang zum Portal')
        ->toContain('vdbs-back-to-top')
        ->and(substr_count($html, '<h1'))->toBe(1);
});

it('lists all role areas as chips with group marks', function () {
    $html = $this->get('/')->getContent();

    foreach (['Vorstand', 'Mitglieder', 'Teamende', 'Schulen', 'Koordination', 'Geschäftsstelle', 'Bildungsreferenten', 'Admins'] as $area) {
        expect($html)->toContain($area);
    }

    expect($html)->toContain('vdbs-chip')->toContain('vdbs-group-mark--1');
});

it('shows the logged-in variant with account actions instead of the login', function () {
    $html = $this->actingAs(User::factory()->create())->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('Mein Konto')
        ->toContain('Sie sind angemeldet')
        ->toContain('Abmelden')
        ->not->toContain('Zur Anmeldung');
});

it('shows contact lines only when they are configured', function () {
    $this->get('/')->assertDontSee('vdbs-contact-block', false);

    config(['portal.home.contact.lines' => [['label' => 'Support', 'value' => 'support@example.org', 'href' => 'mailto:support@example.org']]]);

    $this->get('/')->assertSee('vdbs-contact-block', false)->assertSee('support@example.org');
});

it('does not use placeholder images on the real start page', function () {
    $this->get('/')->assertDontSee('/images/placeholder/', false);
});
