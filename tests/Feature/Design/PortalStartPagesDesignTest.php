<?php

it('wires the start navigation and footer routes used by the issue 50 mockups', function () {
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
    }

    foreach ([
        'portal.contact',
        'portal.imprint',
        'portal.privacy',
        'portal.accessibility',
    ] as $routeName) {
        expect($footer)->toContain("route('{$routeName}')");
    }

    expect($footer)
        ->toContain("'title' => 'Informationen für'")
        ->toContain("'title' => 'Service-Portal'")
        ->toContain("'title' => 'Soziale Medien'")
        ->toContain("'label' => 'Instagram'")
        ->toContain("'label' => 'Homo Politicus'");
});

it('builds the start page with the sections and text shown in the mockup', function () {
    $home = file_get_contents(
        resource_path('views/livewire/identity/home.blade.php'),
    );

    expect($home)
        ->toContain('Willkommen im VDBS Serviceportal')
        ->toContain('Das VDBS Serviceportal')
        ->toContain('Empfohlene Artikel')
        ->toContain('Mehr sicherheit')
        ->toContain('Digitalisierte Verwaltung')
        ->toContain('Eigene Cloud')
        ->toContain('support@portal.vdb.schule')
        ->toContain('kontakt@vdb.schule')
        ->toContain("route('portal.access')")
        ->toContain("route('portal.contact')")
        ->toContain('<x-vdbs.content-split')
        ->toContain('layout="visual-dominant"')
        ->toContain('layout="balanced"')
        ->toContain('side="right"')
        ->toContain('side="left"')
        ->toContain('tone="subtle"')
        ->toContain('caption="Steinerner Torbogen als Symbol für den Zugang zum VDBS Serviceportal"')
        ->toContain('source="Projektbestand VDBS Serviceportal"');
});

it('documents the reusable content split layouts used on the start page', function () {
    $component = file_get_contents(
        resource_path('views/components/vdbs/content-split.blade.php'),
    );
    $css = file_get_contents(
        resource_path('css/vdbs/components/content-split.css'),
    );

    expect($component)
        ->toContain("'variant' => 'image'")
        ->toContain("['image', 'actions']")
        ->toContain('content-split__caption')
        ->toContain('content-split__button-list')
        ->toContain('Bildquelle:')
        ->and($css)
        ->toContain('.content-split--layout-balanced')
        ->toContain('.content-split--layout-visual-dominant')
        ->toContain('.content-split--layout-actions-compact')
        ->toContain('.content-split--layout-actions-wide')
        ->toContain('.content-split--tone-subtle');
});

it('provides the access wizard and contact form shown in the issue mockups', function () {
    $access = file_get_contents(
        resource_path('views/livewire/portal/access.blade.php'),
    );
    $contact = file_get_contents(
        resource_path('views/livewire/portal/contact.blade.php'),
    );

    expect($access)
        ->toContain('Antrag - Zugang zum Portal')
        ->toContain('1. Verbindung')
        ->toContain('2. Kontaktdaten')
        ->toContain('3. ToS &amp; Prüfen')
        ->toContain('Verbindung zum Verein')
        ->toContain('Nachweise (PDF oder Bilder, max. 5 MB pro Datei)')
        ->toContain('Wunsch Nutzername (optional)')
        ->toContain('Prüfen Sie ihre Angaben')
        ->toContain('Antrag absenden')
        ->and($contact)
        ->toContain('Kontaktformular')
        ->toContain('Empfänger')
        ->toContain('Geben Sie Ihrem Anliegen einen Betreff')
        ->toContain('Ihre Nachricht...')
        ->toContain('Ich willige ein');
});

it('matches the service portal information and faq reference screen', function () {
    $information = file_get_contents(
        resource_path('views/components/vdbs/service-portal-information.blade.php'),
    );
    $about = file_get_contents(
        resource_path('views/livewire/portal/about.blade.php'),
    );
    $faq = file_get_contents(
        resource_path('views/livewire/portal/faq.blade.php'),
    );

    expect($information)
        ->toContain('Unser Service-Portal')
        ->toContain('Smart. Vernetzt. Engagiert.')
        ->toContain('Was erwartet Sie im Portal?')
        ->toContain('Ihre Vorteile auf einen Blick')
        ->toContain('Häufig gestellte Fragen')
        ->toContain('Was ist das Service-Portal und wofür wurde es entwickelt?')
        ->toContain('Welche Themenbereiche deckt der Verein ab, die im Portal relevant sind?')
        ->toContain('Wer kann das Service-Portal nutzen?')
        ->toContain('Welche Vorteile bietet mir das Portal?')
        ->toContain('Wie erhalte ich Zugang zum Service-Portal?')
        ->toContain('Wo finde ich Hilfe, wenn ich Probleme mit dem Portal habe?')
        ->toContain('kontakt@portal.vdb.schule')
        ->toContain('Zugang zum Portal')
        ->and($about)
        ->toContain('<x-vdbs.service-portal-information />')
        ->and($faq)
        ->toContain('<x-vdbs.service-portal-information />');
});

it('keeps the issue 50 visual assets in the public portal asset set', function () {
    foreach ([
        'public/images/portal/portal-hero.jpg',
        'public/images/portal/article-security.jpg',
        'public/images/portal/article-admin.jpg',
        'public/images/portal/article-cloud.jpg',
        'public/images/portal/portal-access.jpg',
        'public/images/portal/portal-contact.jpg',
    ] as $path) {
        expect(file_exists(base_path($path)))->toBeTrue();
    }
});
