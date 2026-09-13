<?php

it('wires the start information pages into routes header account menu and footer', function () {
    $routes = file_get_contents(base_path('routes/web.php'));
    $layout = file_get_contents(
        resource_path('views/components/layouts/public.blade.php'),
    );
    $footer = file_get_contents(
        resource_path('views/components/vdbs/portal-footer.blade.php'),
    );

    foreach ([
        'portal.about',
        'portal.access',
        'portal.faq',
        'portal.contact',
        'portal.imprint',
        'portal.privacy',
        'portal.accessibility',
    ] as $routeName) {
        expect($routes)->toContain("->name('{$routeName}')");
    }

    foreach ([
        'portal.about',
        'portal.access',
        'portal.faq',
        'portal.contact',
    ] as $routeName) {
        expect($layout)->toContain("route('{$routeName}')");
        expect($footer)->toContain("route('{$routeName}')");
    }

    foreach ([
        'portal.imprint',
        'portal.privacy',
        'portal.accessibility',
    ] as $routeName) {
        expect($footer)->toContain("route('{$routeName}')");
    }

    expect($layout)
        ->toContain("'label' => 'Über das Portal'")
        ->toContain("'label' => 'Zugang zum Portal'")
        ->toContain("'label' => 'FAQ'")
        ->toContain("'label' => 'Kontakt'");
});

it('provides a view for every start page route', function () {
    foreach ([
        'resources/views/livewire/identity/home.blade.php',
        'resources/views/livewire/portal/about.blade.php',
        'resources/views/livewire/portal/access.blade.php',
        'resources/views/livewire/portal/faq.blade.php',
        'resources/views/livewire/portal/contact.blade.php',
        'resources/views/livewire/portal/imprint.blade.php',
        'resources/views/livewire/portal/privacy.blade.php',
        'resources/views/livewire/portal/accessibility.blade.php',
    ] as $path) {
        expect(file_exists(base_path($path)))->toBeTrue();
    }
});
