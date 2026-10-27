<?php

use App\Modules\Access\Models\User;
use Illuminate\Support\Facades\Blade;

it('renders every supported container width and falls back to default', function () {
    foreach (['narrow', 'medium', 'default', 'wide', 'full'] as $width) {
        expect(Blade::render('<x-vdbs.layout.container width="'.$width.'">x</x-vdbs.layout.container>'))
            ->toContain("vdbs-container--{$width}");
    }

    expect(Blade::render('<x-vdbs.layout.container width="riesig">x</x-vdbs.layout.container>'))
        ->toContain('vdbs-container--default');
});

it('wraps layout content in a container unless the width is full', function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());

    $this->get('/design/seitenlayout')->assertOk()->assertSee('vdbs-container--default', false);

    $full = $this->get('/design/startseite')->assertOk()->getContent();
    expect($full)->toContain('vdbs-main--full')->toContain('vdbs-hero--overlay');
});
