<?php

it('uses the hidden personal areas as the public header context', function () {
    $layout = file_get_contents(
        resource_path('views/components/layouts/public.blade.php'),
    );

    expect($layout)
        ->toContain('$portalAreaCatalog = app(\\App\\Support\\PortalAreaCatalog::class);')
        ->toContain('$personalAreaKey = \\App\\Support\\PortalAreaCatalog::START;')
        ->toContain('$personalAreaKey = \\App\\Support\\PortalAreaCatalog::PROFILE;')
        ->toContain('$portalAreaCatalog->areas(')
        ->toContain(':area="$areaLabel"')
        ->toContain(':area-url="$areaUrl"')
        ->not->toContain('area="VDBS Portal"');
});
