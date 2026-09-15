<?php

it('documents the content-split pattern', function () {
    $page = file_get_contents(
        resource_path('views/design/pages/muster/media-promo.blade.php'),
    );

    expect($page)
        ->toContain('Muster')
        ->toContain('Content Split')
        ->toContain('x-vdbs.content-split');
});
