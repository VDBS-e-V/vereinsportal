<?php

it('documents the media-promo pattern', function () {
    $page = file_get_contents(
        resource_path('views/design/pages/muster/media-promo.blade.php'),
    );

    expect($page)
        ->toContain('Muster')
        ->toContain('MediaPromo');
});
