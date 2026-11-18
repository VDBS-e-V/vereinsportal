<?php

use App\Support\PortalAreas;
use Illuminate\Support\Facades\Blade;

it('renders the area hero with the color, icon, title and optional parts of the area', function () {
    $html = Blade::render('<x-vdbs.layout.area-hero :area="$area" title="Übersicht" greeting="Guten Tag, Erika" lead="Einleitung"><x-slot:actions>Aktion</x-slot:actions><x-slot:aside>Seitenkarte</x-slot:aside></x-vdbs.layout.area-hero>', ['area' => PortalAreas::Vorstand]);

    expect($html)->toContain('vdbs-area-hero')->toContain('--area-tone: var(--group-1)')->toContain('Bereich Vorstand')->toContain('<h1')->toContain('Übersicht')->toContain('Guten Tag, Erika')->toContain('Einleitung')
        ->toContain('Aktion')->toContain('vdbs-area-hero__aside')->toContain('Seitenkarte');

    $plain = Blade::render('<x-vdbs.layout.area-hero :area="$area" title="Konto" />', ['area' => PortalAreas::Konto]);

    expect($plain)->not->toContain('--area-tone: var(--group')->not->toContain('vdbs-area-hero__aside')->not->toContain('vdbs-area-hero__greeting');
});
