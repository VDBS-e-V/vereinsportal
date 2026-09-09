<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makePageTemplatesDesignUser(): User
{
    return User::query()->create([
        'email' => 'design-templates-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('ships page level error state foundations', function () {
    $app = file_get_contents(
        resource_path('css/app.css'),
    );
    $states = file_get_contents(
        resource_path('css/vdbs/components/page-states.css'),
    );

    expect($app)
        ->toContain('components/page-states.css');

    expect($states)
        ->toContain('.error-state__code')
        ->toContain('.error-state__actions')
        ->toContain('.error-state--danger');
});

it('links all complete page templates from the template overview', function () {
    $overview = file_get_contents(
        resource_path('views/design/pages/vorlagen.blade.php'),
    );

    foreach ([
        "route('design.vorlagen.verwaltung-liste')",
        "route('design.vorlagen.verwaltung-detail')",
        "route('design.vorlagen.formular')",
        "route('design.vorlagen.oeffentliche-uebersicht')",
        "route('design.vorlagen.artikel')",
        "route('design.vorlagen.veranstaltung')",
        "route('design.vorlagen.fehlerseiten')",
    ] as $route) {
        expect($overview)->toContain($route);
    }
});

it('renders every complete page template', function () {
    $user = makePageTemplatesDesignUser();

    foreach ([
        '/design/vorlagen/verwaltung-liste',
        '/design/vorlagen/verwaltung-detail',
        '/design/vorlagen/formular',
        '/design/vorlagen/oeffentliche-uebersicht',
        '/design/vorlagen/artikel',
        '/design/vorlagen/veranstaltung',
        '/design/vorlagen/fehlerseiten',
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

it('composes the administration templates from existing component families', function () {
    $list = file_get_contents(
        resource_path('views/design/pages/vorlagen/verwaltung-liste.blade.php'),
    );
    $detail = file_get_contents(
        resource_path('views/design/pages/vorlagen/verwaltung-detail.blade.php'),
    );
    $form = file_get_contents(
        resource_path('views/design/pages/vorlagen/formular.blade.php'),
    );

    expect($list)
        ->toContain('class="search-form"')
        ->toContain('class="filter-bar"')
        ->toContain('class="table-toolbar"')
        ->toContain('class="pagination"');

    expect($detail)
        ->toContain('class="page-tabs"')
        ->toContain('class="key-facts"')
        ->toContain('class="metadata-list"')
        ->toContain('<x-vdbs.file-item')
        ->toContain('<x-vdbs.danger-zone');

    expect($form)
        ->toContain('<x-vdbs.validation-summary')
        ->toContain('form__grid--2')
        ->toContain('aria-invalid="true"');
});

it('composes public editorial event and error templates', function () {
    $public = file_get_contents(
        resource_path('views/design/pages/vorlagen/oeffentliche-uebersicht.blade.php'),
    );
    $article = file_get_contents(
        resource_path('views/design/pages/vorlagen/artikel.blade.php'),
    );
    $event = file_get_contents(
        resource_path('views/design/pages/vorlagen/veranstaltung.blade.php'),
    );
    $errors = file_get_contents(
        resource_path('views/design/pages/vorlagen/fehlerseiten.blade.php'),
    );

    expect($public)
        ->toContain('class="key-facts"')
        ->toContain('<x-vdbs.news-teaser')
        ->toContain('<x-vdbs.event-teaser')
        ->toContain('<x-vdbs.contact-block');

    expect($article)
        ->toContain('class="article"')
        ->toContain('media-frame--16-9')
        ->toContain('<x-vdbs.resource-item');

    expect($event)
        ->toContain('class="event-detail-meta"')
        ->toContain('<x-vdbs.contact-block')
        ->toContain('<x-vdbs.resource-item');

    expect($errors)
        ->toContain('>404<')
        ->toContain('>403<')
        ->toContain('>500<')
        ->toContain('error-state--danger');
});
