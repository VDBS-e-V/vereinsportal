<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeEditorialContentDesignUser(): User
{
    return User::query()->create([
        'email' => 'design-editorial-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('imports the editorial and content component families', function () {
    $app = file_get_contents(
        resource_path('css/app.css'),
    );

    foreach ([
        'components/editorial.css',
        'components/events.css',
        'components/contacts.css',
        'components/resources.css',
        'components/records.css',
    ] as $import) {
        expect($app)->toContain($import);
    }
});

it('ships article news and editorial reading patterns', function () {
    $editorial = file_get_contents(
        resource_path('css/vdbs/components/editorial.css'),
    );
    $component = file_get_contents(
        resource_path('views/components/vdbs/news-teaser.blade.php'),
    );

    expect($editorial)
        ->toContain('.article__header')
        ->toContain('.article__body')
        ->toContain('.editorial-accent')
        ->toContain('.news-list')
        ->toContain('.news-teaser__date');

    expect($component)
        ->toContain("'date'")
        ->toContain("'category' => null")
        ->toContain('<time');
});

it('ships event contact resource and record patterns', function () {
    $events = file_get_contents(
        resource_path('css/vdbs/components/events.css'),
    );
    $contacts = file_get_contents(
        resource_path('css/vdbs/components/contacts.css'),
    );
    $resources = file_get_contents(
        resource_path('css/vdbs/components/resources.css'),
    );
    $records = file_get_contents(
        resource_path('css/vdbs/components/records.css'),
    );

    expect($events)
        ->toContain('.event-teaser')
        ->toContain('.event-detail-meta');

    expect($contacts)
        ->toContain('.contact-list')
        ->toContain('.contact-block__details');

    expect($resources)
        ->toContain('.resource-list')
        ->toContain('.related-links');

    expect($records)
        ->toContain('.key-facts')
        ->toContain('.record-list')
        ->toContain('.record-item__actions');
});

it('ships reusable event contact and resource blade components', function () {
    $event = file_get_contents(
        resource_path('views/components/vdbs/event-teaser.blade.php'),
    );
    $contact = file_get_contents(
        resource_path('views/components/vdbs/contact-block.blade.php'),
    );
    $resource = file_get_contents(
        resource_path('views/components/vdbs/resource-item.blade.php'),
    );

    expect($event)
        ->toContain("'day'")
        ->toContain("'month'")
        ->toContain("'year'")
        ->toContain('sr-only');

    expect($contact)
        ->toContain('mailto:')
        ->toContain('tel:')
        ->toContain('<address>');

    expect($resource)
        ->toContain('@isset($action)')
        ->toContain("'meta' => null");
});

it('renders all new editorial content design groups', function () {
    $user = makeEditorialContentDesignUser();

    foreach ([
        '/design/elemente/artikel-news',
        '/design/elemente/veranstaltungen',
        '/design/elemente/kontakte',
        '/design/elemente/ressourcen',
        '/design/elemente/key-facts',
        '/design/elemente/datensatzlisten',
    ] as $uri) {
        $this
            ->withSession([
                'identity.session_version' => $user->session_version,
                'identity.account_validated_at' => now()->timestamp,
            ])
            ->actingAs($user)
            ->get('http://my.vdb.test'.$uri)
            ->assertOk();
    }
});

it('uses the agreed german date and time formats in event references', function () {
    $events = file_get_contents(
        resource_path('views/design/pages/elemente/veranstaltungen.blade.php'),
    );

    expect($events)
        ->toContain('12.11.2026')
        ->toContain('18:30 Uhr')
        ->toContain('bis 05.11.2026');
});

it('links every editorial content group from the pattern overview', function () {
    $overview = file_get_contents(
        resource_path('views/design/pages/muster.blade.php'),
    );

    foreach ([
        "route('design.elemente.artikel-news')",
        "route('design.elemente.veranstaltungen')",
        "route('design.elemente.kontakte')",
        "route('design.elemente.ressourcen')",
        "route('design.elemente.key-facts')",
        "route('design.elemente.datensatzlisten')",
    ] as $route) {
        expect($overview)->toContain($route);
    }
});

it('uses design titles for nested horizontal navigation labels', function () {
    $layout = file_get_contents(
        resource_path('views/design/layout.blade.php'),
    );

    expect($layout)
        ->toContain("'label' => \$child['label']")
        ->not->toContain("->skip(1)\n                            ->map(");
});

it('links editorial page templates from the template overview', function () {
    $templates = file_get_contents(
        resource_path('views/design/pages/vorlagen.blade.php'),
    );

    expect($templates)
        ->toContain("route('design.vorlagen.artikel')")
        ->toContain("route('design.vorlagen.veranstaltung')")
        ->toContain("route('design.vorlagen.verwaltung-detail')");
});
