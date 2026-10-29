<?php

use App\Modules\Access\Models\User;
use App\Modules\DesignLibrary\Support\Registry;
use App\Support\PortalAreas;

beforeEach(fn () => $this->withoutVite());

it('redirects guests to the login route', function () {
    $this->get('/design')->assertRedirect(route('login'));
});

it('lists every library entry for authenticated users', function () {
    $this->actingAs(designViewer())
        ->get('/design')
        ->assertOk()
        ->assertSee('Design-Bibliothek')
        ->assertSee('Gruppen-Marken');
});

it('renders every registered entry', function () {
    $this->actingAs(designViewer());

    foreach (Registry::all() as $slug => $entry) {
        $this->get(route('design-library.show', $slug))
            ->assertOk()
            ->assertSee($entry['title']);
    }
});

it('returns 404 for unknown entries', function () {
    $this->actingAs(designViewer())
        ->get('/design/gibt-es-nicht')
        ->assertNotFound();
});

it('assigns group marks cyclically to the eight role areas', function () {
    expect(PortalAreas::Start->groupMark())->toBeNull()
        ->and(PortalAreas::Konto->groupMark())->toBeNull()
        ->and(PortalAreas::Vorstand->groupMark())->toBe(1)
        ->and(PortalAreas::Mitglieder->groupMark())->toBe(2)
        ->and(PortalAreas::Teamende->groupMark())->toBe(3)
        ->and(PortalAreas::Schulen->groupMark())->toBe(4)
        ->and(PortalAreas::Koordination->groupMark())->toBe(5)
        ->and(PortalAreas::Geschaeftsstelle->groupMark())->toBe(6)
        ->and(PortalAreas::Bildungsreferenten->groupMark())->toBe(1)
        ->and(PortalAreas::Admins->groupMark())->toBe(2);
});

it('renders the error pages with the new components', function () {
    $this->withoutVite();

    foreach ([403, 404, 419, 500, 503] as $code) {
        expect(view("errors.{$code}")->render())->toContain('Fehler '.$code);
    }
});
