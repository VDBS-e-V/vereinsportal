<?php

it('serves the complete start page set publicly on the portal domain', function () {
    $pages = [
        '/' => 'Willkommen im VDBS Vereinsportal',
        '/ueber-das-portal' => 'Über das Portal',
        '/zugang-zum-portal' => 'Zugang zum Portal',
        '/faq' => 'FAQ',
        '/kontakt' => 'Kontakt',
        '/impressum' => 'Impressum',
        '/datenschutz' => 'Datenschutz',
        '/barrierefreiheit' => 'Barrierefreiheit',
    ];

    foreach ($pages as $path => $heading) {
        $this
            ->get('http://my.vdb.test'.$path)
            ->assertOk()
            ->assertSeeText($heading);
    }
});

it('keeps personal account pages protected while the start page stays public', function () {
    $this
        ->get('http://my.vdb.test/')
        ->assertOk();

    $this
        ->get('http://my.vdb.test/konto')
        ->assertRedirect('http://my.vdb.test/anmelden');
});
