<?php

use App\Modules\Access\Models\User;

beforeEach(fn () => $this->withoutVite());

it('renders the start page for guests with the structure of the design system', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('vdbs-hero--compact')
        ->toContain('vdbs-main--full')
        ->toContain('Schnellzugriff')
        ->toContain('Das VDBS Serviceportal')
        ->toContain('Zugang zum Portal')
        ->toContain('vdbs-back-to-top')
        ->and(substr_count($html, '<h1'))->toBe(1);
});

it('lists all role areas as chips in group colors', function () {
    $html = $this->get('/')->getContent();

    foreach (['Vorstand', 'Mitglieder', 'Teamende', 'Schulen', 'Koordination', 'Geschäftsstelle', 'Bildungsreferenten', 'Admins'] as $area) {
        expect($html)->toContain($area);
    }

    expect($html)->toContain('vdbs-chip')->toContain('vdbs-chip--group-1')->toContain('vdbs-chip--group-6');
});

it('shows the logged-in variant with account actions instead of the login', function () {
    $html = $this->actingAs(User::factory()->create())->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('Mein Konto')
        ->toContain('Sie sind angemeldet')
        ->toContain('Abmelden')
        ->not->toContain('Zur Anmeldung');
});

it('shows the contact block with image and addresses, and falls back without lines', function () {
    $this->get('/')->assertSee('vdbs-contact-block--surface', false)->assertSee('support@portal.vdb.schule')->assertSee('images/portal/kontakt.jpg', false);

    config(['portal.home.contact.lines' => []]);

    $this->get('/')->assertDontSee('vdbs-contact-block', false);
});

it('shows the hero image with centered title and solid quick links below', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('vdbs-hero--image')->toContain('images/portal/hero-bibliothek.jpg')->toContain('vdbs-quick-links--solid')
        ->toContain('vdbs-quick-link--tone-5')->toContain('Über das Portal')->toContain('Hilfe');

    expect(is_file(public_path('images/portal/hero-bibliothek.jpg')) && is_file(public_path('images/portal/kontakt.jpg')))->toBeTrue();
});

it('offers print, theme switch and back to top under "Diese Seite" in the footer', function () {
    $this->get('/')->assertSee('Diese Seite')->assertSee('Drucken')->assertSee('Darstellung wechseln')->assertSee('<a href="#top">Zum Seitenanfang</a>', false);
});

it('does not use placeholder images on the real start page', function () {
    $this->get('/')->assertDontSee('/images/placeholder/', false);
});

it('shows the area chips with group color but without icons or marks', function () {
    $html = $this->get('/')->getContent();

    preg_match('/<nav[^>]*aria-label="Bereiche des Portals".*?<\/nav>/s', $html, $list);

    expect($list)->not->toBeEmpty()
        ->and($list[0])->toContain('vdbs-chip--group-1')
        ->and($list[0])->not->toContain('<svg')
        ->and($list[0])->not->toContain('vdbs-group-mark');
});

it('keeps the numbered group marks where the styleguide shows them', function () {
    $html = \Illuminate\Support\Facades\Blade::render('<x-vdbs.data.group-mark :number="3" />');

    expect($html)->toContain('vdbs-group-mark--3')->toContain('>3<')->toContain('Gruppe 3');

    $plain = \Illuminate\Support\Facades\Blade::render('<x-vdbs.data.group-mark :number="3" :numbered="false" />');

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
