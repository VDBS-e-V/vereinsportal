<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Articles\Models\Article;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function aaUser(?RoleKey $role = null): User
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

function aaForm(array $overrides = []): array
{
    return $overrides + ['title' => 'Mehr Sicherheit im Portal', 'teaser' => 'Kurz erklärt', 'body' => "Erster Absatz.\n\nZweiter Absatz.", 'category' => 'Themenartikel', 'status' => 'published', 'published_at' => now()->subHour()->format('Y-m-d\TH:i'), 'featured' => '1'];
}

it('lets staff and board manage articles and keeps everyone else out', function () {
    $this->get(route('articles.admin.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(aaUser($role))->get(route('articles.admin.index'))->assertOk();
        $this->get(route('articles.admin.create'))->assertOk();
    }

    foreach ([RoleKey::Coordination, RoleKey::Member, RoleKey::Team, RoleKey::Administration] as $role) {
        $this->actingAs(aaUser($role))->get(route('articles.admin.index'))->assertForbidden();
        $this->post(route('articles.admin.store'), aaForm())->assertForbidden();
    }
});

it('creates an article with a unique slug, the creator and a log entry', function () {
    $staff = aaUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->post(route('articles.admin.store'), aaForm())->assertSessionHasNoErrors();
    $this->post(route('articles.admin.store'), aaForm());

    $articles = Article::query()->orderBy('id')->get();
    expect($articles->pluck('slug')->all())->toBe(['mehr-sicherheit-im-portal', 'mehr-sicherheit-im-portal-2'])
        ->and($articles[0]->created_by_user_id)->toBe($staff->id)
        ->and($articles[0]->status)->toBe('published')
        ->and($articles[0]->featured)->toBeTrue()
        ->and($articles[0]->category)->toBe('Themenartikel')
        ->and($articles[0]->published_at)->not->toBeNull();

    $entry = AuditEntry::query()->where('event', 'article.created')->firstOrFail();
    expect($entry->context)->toEqual(['titel' => 'Mehr Sicherheit im Portal']);

    $this->get(route('articles.admin.index'))->assertSee('Mehr Sicherheit im Portal')->assertSee('Veröffentlicht');
});

it('validates the article form', function () {
    $this->actingAs(aaUser(RoleKey::AdministrationStaff));

    $this->post(route('articles.admin.store'), [])->assertSessionHasErrors(['title', 'body', 'status']);
    $this->post(route('articles.admin.store'), aaForm(['status' => 'weg']))->assertSessionHasErrors('status');
    $this->post(route('articles.admin.store'), aaForm(['teaser' => str_repeat('x', 301)]))->assertSessionHasErrors('teaser');
    $this->post(route('articles.admin.store'), aaForm(['image' => UploadedFile::fake()->create('bild.jpg', 10, 'image/jpeg')]))->assertSessionHasErrors(['image_alt', 'image_credit']);
    $this->post(route('articles.admin.store'), aaForm(['image' => UploadedFile::fake()->create('datei.pdf', 10, 'application/pdf'), 'image_alt' => 'x']))->assertSessionHasErrors('image');
    $this->post(route('articles.admin.store'), aaForm(['image' => UploadedFile::fake()->create('gross.jpg', 5000, 'image/jpeg'), 'image_alt' => 'x']))->assertSessionHasErrors('image');

    expect(Article::query()->count())->toBe(0);
});

it('stores the image privately and serves it through the article route', function () {
    $this->actingAs(aaUser(RoleKey::AdministrationStaff))->post(route('articles.admin.store'), aaForm(['image' => UploadedFile::fake()->create('bild.jpg', 50, 'image/jpeg'), 'image_alt' => 'Ein Schloss', 'image_credit' => 'Foto: Beispiel']))->assertSessionHasNoErrors();

    $article = Article::query()->firstOrFail();
    expect($article->image_path)->toStartWith('articles/')->and($article->image_alt)->toBe('Ein Schloss')->and($article->image_credit)->toBe('Foto: Beispiel');
    Storage::disk('local')->assertExists($article->image_path);

    $this->app['auth']->forgetGuards();
    $this->get(route('articles.public.image', $article->slug))->assertOk();
    $this->get('/')->assertSee(route('articles.public.image', $article->slug), false)->assertSee('Foto: Beispiel', false);
});

it('edits an article, replaces and removes the image and records the changed fields', function () {
    $staff = aaUser(RoleKey::BoardMember);
    $this->actingAs($staff)->post(route('articles.admin.store'), aaForm(['image' => UploadedFile::fake()->create('bild.jpg', 50, 'image/jpeg'), 'image_alt' => 'Alt', 'image_credit' => 'Foto: Test']));
    $article = Article::query()->firstOrFail();
    $old = $article->image_path;

    $this->get(route('articles.admin.edit', $article))->assertOk()->assertSee('Mehr Sicherheit im Portal')->assertSee('Bild entfernen');

    $this->put(route('articles.admin.update', $article), aaForm(['title' => 'Neuer Titel', 'image' => UploadedFile::fake()->create('neu.png', 50, 'image/png'), 'image_alt' => 'Neu', 'image_credit' => 'Foto: Neu']))->assertSessionHasNoErrors();
    $article->refresh();
    expect($article->title)->toBe('Neuer Titel')->and($article->slug)->toBe('mehr-sicherheit-im-portal')->and($article->image_path)->not->toBe($old)->and($article->image_path)->toEndWith('.png');
    Storage::disk('local')->assertMissing($old);
    Storage::disk('local')->assertExists($article->image_path);

    $entry = AuditEntry::query()->where('event', 'article.updated')->firstOrFail();
    expect($entry->context['felder'])->toContain('title')->toContain('image_path');

    $this->put(route('articles.admin.update', $article), aaForm(['title' => 'Neuer Titel', 'remove_image' => '1']));
    $article->refresh();
    expect($article->image_path)->toBeNull()->and($article->image_alt)->toBeNull();
});

it('keeps drafts and planned articles off the public pages', function () {
    $this->actingAs(aaUser(RoleKey::AdministrationStaff));
    $this->post(route('articles.admin.store'), aaForm(['title' => 'Entwurfsartikel', 'status' => 'draft']));
    $this->post(route('articles.admin.store'), aaForm(['title' => 'Geplanter Artikel', 'published_at' => now()->addDays(3)->format('Y-m-d\TH:i')]));
    $this->post(route('articles.admin.store'), aaForm(['title' => 'Sichtbarer Artikel']));

    expect(Article::query()->where('title', 'Entwurfsartikel')->first()->published_at)->toBeNull();

    $this->app['auth']->forgetGuards();
    $this->get(route('articles.public.index'))->assertSee('Sichtbarer Artikel')->assertDontSee('Entwurfsartikel')->assertDontSee('Geplanter Artikel');
    $this->get('/')->assertSee('Sichtbarer Artikel')->assertDontSee('Geplanter Artikel');

    $this->actingAs(aaUser(RoleKey::AdministrationStaff))->get(route('articles.admin.index', ['status' => 'draft']))->assertSee('Entwurfsartikel')->assertDontSee('Sichtbarer Artikel');
    $this->get(route('articles.admin.index'))->assertSee('Geplant');
});

it('deletes an article with its image and logs it', function () {
    $this->actingAs(aaUser(RoleKey::AdministrationStaff))->post(route('articles.admin.store'), aaForm(['image' => UploadedFile::fake()->create('bild.jpg', 50, 'image/jpeg'), 'image_alt' => 'Alt', 'image_credit' => 'Foto: Test']));
    $article = Article::query()->firstOrFail();

    $this->delete(route('articles.admin.destroy', $article))->assertRedirect(route('articles.admin.index'));

    expect(Article::query()->count())->toBe(0)->and(AuditEntry::query()->where('event', 'article.deleted')->exists())->toBeTrue();
    Storage::disk('local')->assertMissing($article->image_path);
});

it('searches the article list', function () {
    $this->actingAs(aaUser(RoleKey::AdministrationStaff));
    $this->post(route('articles.admin.store'), aaForm(['title' => 'Alpha Artikel']));
    $this->post(route('articles.admin.store'), aaForm(['title' => 'Beta Artikel']));

    $this->get(route('articles.admin.index', ['q' => 'Alpha']))->assertSee('Alpha Artikel')->assertDontSee('Beta Artikel');
});
