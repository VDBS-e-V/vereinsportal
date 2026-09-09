<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeAdvancedElementsDesignUser(): User
{
    return User::query()->create([
        'email' => 'design-advanced-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('imports the advanced component foundations', function () {
    $app = file_get_contents(
        resource_path('css/app.css'),
    );

    foreach ([
        "components/loading.css",
        "components/validation.css",
        "components/danger.css",
        "components/dialogs.css",
        "components/media.css",
        "components/files.css",
    ] as $import) {
        expect($app)->toContain($import);
    }
});

it('ships loading states with reduced motion support', function () {
    $loading = file_get_contents(
        resource_path('css/vdbs/components/loading.css'),
    );
    $component = file_get_contents(
        resource_path('views/components/vdbs/loading-state.blade.php'),
    );

    expect($loading)
        ->toContain('.loading-state__spinner')
        ->toContain('.progress progress')
        ->toContain('@media (prefers-reduced-motion: reduce)');

    expect($component)
        ->toContain('aria-busy="true"')
        ->toContain("'live' => false")
        ->toContain('role="status"');
});

it('ships linked validation summaries without replacing field errors', function () {
    $validation = file_get_contents(
        resource_path('css/vdbs/components/validation.css'),
    );
    $component = file_get_contents(
        resource_path('views/components/vdbs/validation-summary.blade.php'),
    );
    $page = file_get_contents(
        resource_path('views/design/pages/elemente/validierung.blade.php'),
    );

    expect($validation)
        ->toContain('.validation-summary__list')
        ->toContain('.validation-summary:focus');

    expect($component)
        ->toContain('tabindex="-1"');

    expect($page)
        ->toContain('href="#validation-email"')
        ->toContain('href="#validation-name"')
        ->toContain('aria-invalid="true"');
});

it('ships destructive action context without typed confirmation phrases', function () {
    $danger = file_get_contents(
        resource_path('css/vdbs/components/danger.css'),
    );
    $component = file_get_contents(
        resource_path('views/components/vdbs/danger-zone.blade.php'),
    );
    $page = file_get_contents(
        resource_path('views/design/pages/elemente/bestaetigung.blade.php'),
    );

    expect($danger)
        ->toContain('.danger-zone__actions')
        ->toContain('.confirmation-note');

    expect($component)
        ->toContain("'title'");

    expect($page)
        ->toContain('data-vdbs-dialog-open')
        ->not->toContain('type="text" name="delete-confirmation"');
});

it('ships native dialog behavior and restores focus', function () {
    $dialog = file_get_contents(
        resource_path('views/components/vdbs/dialog.blade.php'),
    );
    $script = file_get_contents(
        resource_path('js/portal-dialog.js'),
    );
    $app = file_get_contents(
        resource_path('js/app.js'),
    );

    expect($dialog)
        ->toContain('<dialog')
        ->toContain('data-vdbs-dialog')
        ->toContain('aria-labelledby')
        ->toContain('data-vdbs-dialog-close');

    expect($script)
        ->toContain('showModal()')
        ->toContain('dialog.close()')
        ->toContain('returnFocus.focus()');

    expect($app)
        ->toContain("import './portal-dialog';");
});

it('ships the agreed media ratios and object fit modes', function () {
    $media = file_get_contents(
        resource_path('css/vdbs/components/media.css'),
    );

    expect($media)
        ->toContain('aspect-ratio: 3 / 2;')
        ->toContain('aspect-ratio: 16 / 9;')
        ->toContain('aspect-ratio: 1;')
        ->toContain('object-fit: cover;')
        ->toContain('object-fit: contain;')
        ->toContain('.media-figure__source');
});

it('ships native file upload and reusable file rows', function () {
    $files = file_get_contents(
        resource_path('css/vdbs/components/files.css'),
    );
    $component = file_get_contents(
        resource_path('views/components/vdbs/file-item.blade.php'),
    );
    $page = file_get_contents(
        resource_path('views/design/pages/elemente/dateien.blade.php'),
    );

    expect($files)
        ->toContain('::file-selector-button')
        ->toContain('.file-list')
        ->toContain('.file-item__actions');

    expect($component)
        ->toContain('<x-vdbs.badge>')
        ->toContain('@isset($status)')
        ->toContain('@isset($actions)');

    expect($page)
        ->toContain('type="file"')
        ->toContain('.pdf,.xlsx,.xls,.csv,.png,.jpg,.jpeg')
        ->toContain('<x-vdbs.file-item');
});

it('renders all newly added element groups', function () {
    $user = makeAdvancedElementsDesignUser();

    foreach ([
        '/design/elemente/loading',
        '/design/elemente/validierung',
        '/design/elemente/bestaetigung',
        '/design/elemente/dialoge',
        '/design/elemente/medien',
        '/design/elemente/dateien',
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

it('links advanced groups from their correct design overviews', function () {
    $elements = file_get_contents(
        resource_path('views/design/pages/elemente.blade.php'),
    );
    $patterns = file_get_contents(
        resource_path('views/design/pages/muster.blade.php'),
    );

    foreach ([
        "route('design.elemente.loading')",
        "route('design.elemente.validierung')",
        "route('design.elemente.bestaetigung')",
        "route('design.elemente.dialoge')",
        "route('design.elemente.dateien')",
    ] as $route) {
        expect($elements)->toContain($route);
    }

    expect($patterns)
        ->toContain("route('design.elemente.medien')");

    expect($elements)
        ->toContain('<h2>Overlays</h2>')
        ->toContain('<h2>Dateien</h2>');
});

it('documents modal accessibility and file media rules', function () {
    $dialogs = file_get_contents(
        resource_path('views/design/pages/elemente/dialoge.blade.php'),
    );
    $media = file_get_contents(
        resource_path('views/design/pages/elemente/medien.blade.php'),
    );
    $files = file_get_contents(
        resource_path('views/design/pages/elemente/dateien.blade.php'),
    );

    expect($dialogs)
        ->toContain('Fokus zum auslösenden Element zurück')
        ->toContain('native <code>dialog</code>-Element');

    expect($media)
        ->toContain('<figure class="media-figure">')
        ->toContain('<figcaption class="media-figure__caption">');

    expect($files)
        ->toContain('PDF')
        ->toContain('Tabellen')
        ->toContain('Bilder');
});
