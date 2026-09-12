<?php

it('keeps portal and administration start links distinct in the administration shell', function () {
    $layout = file_get_contents(
        resource_path('views/layouts/administration.blade.php'),
    );

    expect($layout)
        ->toContain("\$portalHomeUrl = route('my.home');")
        ->toContain("\$areaHomeUrl = route('administration.home');")
        ->toContain(':home-url="$portalHomeUrl"')
        ->toContain(':area-url="$areaHomeUrl"')
        ->not->toContain("['label' => 'Portal'");
});
