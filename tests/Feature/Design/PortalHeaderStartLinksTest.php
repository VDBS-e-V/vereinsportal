<?php

it('keeps portal and staff-area start links distinct in the administration shell', function () {
    $layout = file_get_contents(
        resource_path('views/layouts/administration.blade.php'),
    );

    expect($layout)
        ->toContain("\$portalHomeUrl = route('my.home');")
        ->toContain("\$isBoardArea = request()->is('vorstand', 'vorstand/*');")
        ->toContain("\$isCoordinationArea = request()->is('koordination', 'koordination/*');")
        ->toContain("? route('board.home')")
        ->toContain("route('coordination.home')")
        ->toContain("route('administration.home')")
        ->toContain(':home-url="$portalHomeUrl"')
        ->toContain(':area-url="$areaHomeUrl"')
        ->not->toContain("['label' => 'Portal'");
});
