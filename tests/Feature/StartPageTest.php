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

it('shows the areas with the group colors and shapes but without the group numbers', function () {
    $html = $this->get('/')->getContent();

    preg_match_all('/<span class="vdbs-group-mark vdbs-group-mark--\d"[^>]*>(.*?)<\/span>/s', $html, $marks);

    expect($marks[0])->not->toBeEmpty()
        ->and(implode('', $marks[1]))->toBe('')
        ->and($html)->not->toContain('aria-label="Gruppe 1')
        ->and($html)->toContain('vdbs-group-mark--6');
});

it('keeps the numbered group marks where the styleguide shows them', function () {
    $html = view('components.vdbs.data.group-mark', ['number' => 3])->render();

    expect($html)->toContain('vdbs-group-mark--3')->toContain('>3<')->toContain('Gruppe 3');

    $plain = view('components.vdbs.data.group-mark', ['number' => 3, 'numbered' => false])->render();

    expect($plain)->toContain('vdbs-group-mark--3')->not->toContain('>3<')->not->toContain('Gruppe 3')->toContain('aria-hidden="true"');
});

it('shows the access block with the image and its source on the start page', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('images/portal/zugang.jpg')->toContain('alt="Steinerner Torbogen')->toContain('Bild aus dem bisherigen VDBS-Portal')
        ->toContain('Hilfe kontaktieren')->toContain('Zugang zum Portal')->toContain('vdbs-split__media')
        ->not->toContain('/images/placeholder/');

    expect(is_file(public_path('images/portal/zugang.jpg')))->toBeTrue();

    config(['portal.home.access_image.src' => null]);
    $this->get('/')->assertOk()->assertDontSee('vdbs-split__media', false);
});

it('shows the same image on the page about the portal', function () {
    $this->get(route('portal.about'))->assertOk()->assertSee('images/portal/zugang.jpg', false);
});
