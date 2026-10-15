<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeServicePortalTemplateDesignUser(): User
{
    return User::query()->create([
        'email' => 'design-serviceportal-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('links the screenshot based service portal templates from the design overview', function () {
    $overview = file_get_contents(
        resource_path('views/design/pages/vorlagen.blade.php'),
    );

    expect($overview)
        ->toContain("route('design.vorlagen.serviceportal-start')")
        ->toContain("route('design.vorlagen.serviceportal-inhalt')")
        ->toContain("route('design.vorlagen.serviceportal-formular')");
});

it('renders the service portal design templates', function () {
    $user = makeServicePortalTemplateDesignUser();

    foreach ([
        '/design/vorlagen/serviceportal-start',
        '/design/vorlagen/serviceportal-inhalt',
        '/design/vorlagen/serviceportal-formular',
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

it('ships reusable service portal page templates', function () {
    $start = file_get_contents(
        resource_path('views/components/vdbs/templates/service-start.blade.php'),
    );
    $content = file_get_contents(
        resource_path('views/components/vdbs/templates/service-content.blade.php'),
    );
    $information = file_get_contents(
        resource_path('views/components/vdbs/templates/service-information.blade.php'),
    );
    $panel = file_get_contents(
        resource_path('views/components/vdbs/templates/service-panel.blade.php'),
    );

    expect($start)
        ->toContain('$attributes->class([\'service-start\'])')
        ->toContain('$hero')
        ->toContain('$overview')
        ->toContain('$articles')
        ->toContain('$access')
        ->toContain('$contact');

    expect($content)
        ->toContain('$attributes->class([\'service-information\'])')
        ->toContain('$intro')
        ->toContain('$faq')
        ->toContain('$callout');

    expect($information)
        ->toContain('class="service-information"')
        ->toContain('Unser Service-Portal')
        ->toContain('Häufig gestellte Fragen')
        ->toContain("route('portal.access')")
        ->toContain("route('portal.contact')");

    expect($panel)
        ->toContain('mockup-page')
        ->toContain('mockup-panel')
        ->toContain('titlePosition');
});

it('uses the new service templates in the product pages', function () {
    $home = file_get_contents(
        resource_path('views/livewire/identity/home.blade.php'),
    );
    $about = file_get_contents(
        resource_path('views/livewire/portal/about.blade.php'),
    );
    $faq = file_get_contents(
        resource_path('views/livewire/portal/faq.blade.php'),
    );
    $contact = file_get_contents(
        resource_path('views/livewire/portal/contact.blade.php'),
    );

    expect($home)
        ->toContain('<x-vdbs.templates.service-start>')
        ->toContain('class="service-hero__title"')
        ->toContain('Willkommen im VDBS Serviceportal');

    expect($about)
        ->toContain('<x-vdbs.templates.service-information />');

    expect($faq)
        ->toContain('<x-vdbs.templates.service-information />');

    expect($contact)
        ->toContain('<x-vdbs.templates.service-panel')
        ->toContain('title-position="inside"');
});

it('imports the service portal template styles', function () {
    $app = file_get_contents(resource_path('css/app.css'));
    $styles = file_get_contents(
        resource_path('css/vdbs/templates/service-portal.css'),
    );

    expect($app)
        ->toContain("@import './vdbs/templates/service-portal.css';");

    expect($styles)
        ->toContain('.service-hero__media')
        ->toContain('.service-hero__title');
});
