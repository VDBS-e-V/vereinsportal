<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Mailings\Models\MailSnippet;
use App\Modules\Mailings\Models\MailTemplate;
use App\Modules\Mailings\Services\MailDefaultsLoader;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Support\MailDefaults;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function mdStaff(): User
{
    $p = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', RoleKey::AdministrationStaff->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

it('loads the default templates and snippets once and never overwrites', function () {
    $result = app(MailDefaultsLoader::class)->load();

    expect($result['templates'])->toBe(count(MailDefaults::templates()))->and($result['snippets'])->toBe(count(MailDefaults::snippets()))
        ->and(MailTemplate::query()->where('kind', 'general')->count())->toBe(15);

    MailTemplate::query()->where('name', 'Willkommen im Verein')->update(['subject' => 'Eigener Betreff']);
    MailSnippet::query()->where('name', 'Gruß mit Absender')->delete();

    $again = app(MailDefaultsLoader::class)->load();

    expect($again)->toBe(['templates' => 0, 'snippets' => 1])
        ->and(MailTemplate::query()->where('name', 'Willkommen im Verein')->value('subject'))->toBe('Eigener Betreff');
});

it('uses only valid placeholders in all defaults and renders every template', function () {
    foreach (MailDefaults::templates() as $row) {
        expect(MailPlaceholders::unknown($row['subject'], 'general'))->toBe([], $row['name'])
            ->and(MailPlaceholders::unknown($row['body'], 'general'))->toBe([], $row['name']);
    }

    foreach (MailDefaults::snippets() as $row) {
        expect(MailPlaceholders::unknown($row['body'], 'general'))->toBe([], $row['name']);
    }

    $staff = mdStaff();
    app(MailDefaultsLoader::class)->load();

    foreach (MailTemplate::query()->where('kind', 'general')->get() as $template) {
        $preview = app(Mailings::class)->previewGroups($template->subject, $template->body, ['title' => 'Test', 'date' => '2027-05-01', 'time' => '18:00'], $staff);
        expect($preview['html'])->not->toContain('{')->and($preview['subject'])->not->toContain('{');
    }
});

it('filters templates by category and search, rejects duplicate names and deletes several at once', function () {
    $this->actingAs(mdStaff());
    app(MailDefaultsLoader::class)->load();

    $this->get(route('mailings.templates.index', ['kategorie' => 'feedback']))->assertOk()->assertSee('Feedback-Bitte an Lehrkräfte')->assertDontSee('Willkommen im Verein');
    $this->get(route('mailings.templates.index', ['q' => 'Willkommen']))->assertOk()->assertSee('Willkommen im Verein')->assertDontSee('Feedback-Bitte an Lehrkräfte');
    $this->get(route('mailings.templates.snippets', ['kategorie' => 'kontakt']))->assertOk()->assertSee('Kontaktblock Vorstand')->assertDontSee('Förmliche Anrede');

    $this->post(route('mailings.templates.store'), ['name' => 'Willkommen im Verein', 'subject' => 'x', 'body' => '<p>x</p>'])->assertSessionHasErrors('name');
    $this->post(route('mailings.templates.store'), ['name' => 'Neu', 'category' => 'team', 'audience' => 'team', 'subject' => 'x', 'body' => '<p>x</p>'])->assertRedirect();
    expect(MailTemplate::query()->where('name', 'Neu')->value('category'))->toBe('team');

    $ids = MailTemplate::query()->whereIn('name', ['Neu', 'Willkommen im Verein'])->pluck('id')->all();
    $this->post(route('mailings.templates.destroy-many'), ['ids' => $ids])->assertRedirect();
    expect(MailTemplate::query()->whereIn('id', $ids)->count())->toBe(0);

    $snippetIds = MailSnippet::query()->limit(3)->pluck('id')->all();
    $this->post(route('mailings.templates.snippets.destroy-many'), ['ids' => $snippetIds])->assertRedirect();
    expect(MailSnippet::query()->whereIn('id', $snippetIds)->count())->toBe(0);
});
