<?php

use App\Support\PortalAreas;
use App\Support\PortalNavigation;

beforeEach(function () {
    $this->withoutVite();
});

it('serves the documentation of the design packages publicly', function () {
    $this->get(route('portal.design-packages'))->assertOk()
        ->assertSee('Design-Pakete entwickeln')->assertSee('design:package')->assertSee('design:component')->assertSee('design:check')
        ->assertSee('--dry-run')->assertSee('design-packages:imports')->assertSee('@props', false);
});

it('belongs to the start area and shows every command of the tool', function () {
    expect(PortalNavigation::areaForRoute('portal.design-packages'))->toBe(PortalAreas::Start);

    $html = $this->get(route('portal.design-packages'))->getContent();

    foreach (array_keys(Artisan::all()) as $command) {
        if (str_starts_with($command, 'design:')) {
            expect($html)->toContain($command);
        }
    }
});
