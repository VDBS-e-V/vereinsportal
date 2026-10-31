<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Pages\Enums\PageKey;
use App\Modules\Pages\Models\Page;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function lpUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('key', $role->value)->value('id'),
            'source' => RoleAssignmentSource::Manual,
            'starts_at' => now()->subMinute(),
        ]);
    }

    return $user;
}

it('shows the four legal pages publicly with default text', function () {
    foreach (PageKey::cases() as $key) {
        $this->get(route('pages.show', $key->value))->assertOk()->assertSee($key->title());
    }

    $this->get('/impressum')->assertSee('Verband für Demokratiebildung und Bibliotheken an Schulen')->assertSee('Onkel-Bräsig-Str. 76/78')
        ->assertDontSee('Dies ist der Standardtext');
});

it('links the footer and the consent texts to the legal pages', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('href="/impressum"')->toContain('href="/datenschutz"')->toContain('href="/barrierefreiheit"');
    expect(config('portal.legal.terms_url'))->toBe('/nutzungsbedingungen')->and(config('portal.legal.privacy_url'))->toBe('/datenschutz');
});

it('lets staff and board edit pages and keeps everyone else out', function () {
    $this->get(route('pages.admin.index'))->assertRedirect(route('login'));

    foreach ([null, RoleKey::Coordination, RoleKey::Administration] as $role) {
        $this->actingAs(lpUser($role))->get(route('pages.admin.index'))->assertForbidden();
    }

    foreach ([RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(lpUser($role))->get(route('pages.admin.index'))->assertOk()->assertSee('Impressum')->assertSee('Standardtext');
    }
});

it('saves an edited text, shows it publicly as safe html and logs only the page name', function () {
    $user = lpUser(RoleKey::AdministrationStaff);

    $this->actingAs($user)->get(route('pages.admin.edit', 'datenschutz'))->assertOk()->assertSee('Platzhalter');

    $this->actingAs($user)->put(route('pages.admin.update', 'datenschutz'), ['body' => "## Verantwortlicher\n\nUnser **neuer** Text <script>alert(1)</script> hier."])
        ->assertRedirect(route('pages.admin.edit', 'datenschutz'));

    expect(Page::query()->where('key', 'datenschutz')->value('updated_by_user_id'))->toBe($user->id);

    $this->get('/datenschutz')->assertOk()->assertSee('<strong>neuer</strong>', false)->assertDontSee('<script>alert', false)->assertDontSee('Platzhalter');

    $entry = AuditEntry::query()->where('event', 'page.updated')->latest('id')->first();
    expect($entry)->not->toBeNull()->and(json_encode($entry->context, JSON_UNESCAPED_UNICODE))->toContain('Datenschutzerklärung')->not->toContain('neuer');
});

it('validates the text and rejects unknown pages', function () {
    $user = lpUser(RoleKey::BoardMember);

    $this->actingAs($user)->from(route('pages.admin.edit', 'impressum'))->put(route('pages.admin.update', 'impressum'), ['body' => ''])->assertSessionHasErrors('body');
    $this->actingAs($user)->get('/verwaltung/seiten/unbekannt')->assertNotFound();
    $this->get('/unbekannt-seite')->assertNotFound();
});

it('offers the pages in the Geschäftsstelle navigation only with the permission', function () {
    $this->actingAs(lpUser(RoleKey::AdministrationStaff))->get(route('pages.admin.index'))->assertSee('Seiten');
});
