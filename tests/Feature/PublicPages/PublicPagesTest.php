<?php

use App\Mail\ContactMessageMail;
use App\Modules\Access\Models\User;
use App\Modules\Articles\Models\Article;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    Mail::fake();
});

function ppArticle(array $overrides = []): Article
{
    static $n = 0;
    $n++;

    return Article::query()->create($overrides + [
        'slug' => 'artikel-'.$n,
        'title' => 'Artikel '.$n,
        'body' => 'Text des Artikels '.$n,
        'category' => 'Themenartikel',
        'status' => Article::STATUS_PUBLISHED,
        'published_at' => now()->subHour(),
        'featured' => false,
    ]);
}

function ppContact(array $overrides = []): array
{
    return $overrides + ['first_name' => 'Max', 'last_name' => 'Mustermann', 'email' => 'Max@Beispiel.example', 'recipient' => 'board', 'subject' => 'Frage zur Mitgliedschaft', 'message' => "Guten Tag,\nich habe eine Frage.", 'consent' => '1'];
}

// ---------- Startseite ----------

it('shows the new start page with quick links, areas, access and contact', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('Willkommen im VDBS Serviceportal')
        ->toContain('Schnellzugriff')
        ->toContain('Über das Portal')
        ->toContain('Zugang zum Portal')
        ->toContain('Hilfe')
        ->toContain('Die Bereiche des Portals')
        ->toContain('Zum Kontaktformular')
        ->toContain(route('portal.access'))
        ->not->toContain('Empfohlene Artikel');
});

it('shows recommended articles on the start page, featured first, at most three', function () {
    ppArticle(['title' => 'Alter Artikel', 'published_at' => now()->subDays(5)]);
    ppArticle(['title' => 'Empfohlener Artikel', 'featured' => true, 'published_at' => now()->subDays(9), 'category' => 'Hintergrund']);
    ppArticle(['title' => 'Neuer Artikel', 'published_at' => now()->subDay()]);
    ppArticle(['title' => 'Mittlerer Artikel', 'published_at' => now()->subDays(2)]);
    ppArticle(['title' => 'Entwurf', 'status' => Article::STATUS_DRAFT]);
    ppArticle(['title' => 'Zukunftsartikel', 'published_at' => now()->addDay()]);

    $html = $this->get('/')->assertOk()->assertSee('Empfohlene Artikel')->assertSee('Alle Artikel anzeigen')->getContent();

    expect($html)->toContain('Empfohlener Artikel')->toContain('Neuer Artikel')->toContain('Mittlerer Artikel')
        ->not->toContain('Alter Artikel')->not->toContain('Entwurf')->not->toContain('Zukunftsartikel')
        ->and(strpos($html, 'Empfohlener Artikel'))->toBeLessThan(strpos($html, 'Neuer Artikel'));
});

it('keeps the placeholder images out of the public pages', function () {
    foreach (['/', route('portal.about'), route('portal.access'), route('portal.contact'), route('articles.public.index')] as $url) {
        $this->get($url)->assertDontSee('/images/placeholder/', false);
    }
});

// ---------- Über das Portal ----------

it('explains the portal and answers the frequent questions', function () {
    $html = $this->get(route('portal.about'))->assertOk()->getContent();

    expect($html)->toContain('Unser Service-Portal')->toContain('Smart. Vernetzt. Engagiert.')
        ->toContain('Was erwartet Sie im Portal?')->toContain('Für Teamer:innen')->toContain('Für Vereinsmitglieder')->toContain('Für die Verwaltung')->toContain('Für Schulen')
        ->toContain('Ihre Vorteile auf einen Blick')->toContain('id="portal-faq"')->toContain('Häufig gestellte Fragen')
        ->toContain('Was ist das Service-Portal und wofür wurde es entwickelt?')->toContain('Wo finde ich Hilfe, wenn ich Probleme mit dem Portal habe?')
        ->and(substr_count($html, 'vdbs-accordion__item'))->toBeGreaterThanOrEqual(7);
});

// ---------- Kontaktformular ----------

it('shows the contact form with all fields and the configured recipients', function () {
    $html = $this->get(route('portal.contact'))->assertOk()->getContent();

    expect($html)->toContain('Kontaktformular')
        ->toContain('name="first_name"')->toContain('name="last_name"')->toContain('name="email"')->toContain('name="recipient"')->toContain('name="subject"')->toContain('name="message"')->toContain('name="consent"')
        ->toContain('Allgemeine Anfrage')->toContain('Vorstand')->toContain('Portal-Support')
        ->toContain('Absenden');
});

it('sends the message to the chosen recipient with a reply address and stores nothing', function () {
    $this->post(route('portal.contact.send'), ppContact())->assertRedirect(route('portal.contact'))->assertSessionHas('success');

    Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $mail): bool => $mail->hasTo('vorstand@vdb.schule')
        && $mail->hasReplyTo('max@beispiel.example')
        && $mail->senderName === 'Max Mustermann'
        && $mail->recipientLabel === 'Vorstand'
        && str_contains($mail->body, 'ich habe eine Frage')
        && str_contains($mail->envelope()->subject, 'Frage zur Mitgliedschaft'));
    Mail::assertSentCount(1);
});

it('uses the recipient address from the configuration', function () {
    config(['portal.contact_form.recipients.support.email' => 'hilfe@example.org']);

    $this->post(route('portal.contact.send'), ppContact(['recipient' => 'support']));

    Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $mail): bool => $mail->hasTo('hilfe@example.org'));
});

it('validates the contact form', function () {
    $this->post(route('portal.contact.send'), [])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'recipient', 'subject', 'message', 'consent']);
    $this->post(route('portal.contact.send'), ppContact(['email' => 'kaputt']))->assertSessionHasErrors('email');
    $this->post(route('portal.contact.send'), ppContact(['recipient' => 'nirgendwo']))->assertSessionHasErrors('recipient');
    $this->post(route('portal.contact.send'), ppContact(['consent' => null]))->assertSessionHasErrors('consent');
    $this->post(route('portal.contact.send'), ppContact(['message' => str_repeat('x', 5001)]))->assertSessionHasErrors('message');

    Mail::assertNothingSent();
});

it('answers the hidden bot field like normal and sends nothing', function () {
    $this->post(route('portal.contact.send'), ppContact(['website' => 'http://spam.example']))->assertRedirect(route('portal.contact'))->assertSessionHas('success');

    Mail::assertNothingSent();
});

it('limits the contact form per visitor', function () {
    foreach (range(1, 5) as $i) {
        $this->post(route('portal.contact.send'), ppContact(['subject' => 'Frage '.$i]))->assertRedirect(route('portal.contact'));
    }

    $this->post(route('portal.contact.send'), ppContact())->assertStatus(429);
});

it('keeps the entered values and shows an error when sending fails', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Mailserver nicht erreichbar'));

    $this->from(route('portal.contact'))->post(route('portal.contact.send'), ppContact())->assertRedirect(route('portal.contact'))->assertSessionHas('error')->assertSessionHasInput('subject', 'Frage zur Mitgliedschaft');
});

// ---------- Artikel (öffentlich) ----------

it('lists published articles with the newest first and paginates', function () {
    foreach (range(1, 10) as $i) {
        ppArticle(['title' => 'Liste '.$i, 'published_at' => now()->subMinutes(100 - $i)]);
    }
    ppArticle(['title' => 'Nur Entwurf', 'status' => Article::STATUS_DRAFT]);

    $first = $this->get(route('articles.public.index'))->assertOk()->assertSee('Liste 10')->assertDontSee('Nur Entwurf')->getContent();
    expect(strpos($first, 'Liste 10'))->toBeLessThan(strpos($first, 'Liste 9'));

    $this->get(route('articles.public.index', ['page' => 2]))->assertSee('Liste 1')->assertDontSee('Liste 10');
});

it('shows an article with text, category, date and image source', function () {
    $article = ppArticle(['title' => 'Mehr Sicherheit', 'category' => 'Themenartikel', 'body' => "Erster Absatz.\n\n## Zwischenüberschrift\n\n**fett** und [Link](https://example.org)", 'image_path' => 'articles/x.jpg', 'image_alt' => 'Ein Schloss', 'image_credit' => 'Foto: Beispiel']);

    $html = $this->get(route('articles.public.show', $article->slug))->assertOk()->getContent();

    expect($html)->toContain('Mehr Sicherheit')->toContain('Themenartikel')->toContain('<h2>Zwischenüberschrift</h2>')->toContain('<strong>fett</strong>')->toContain('href="https://example.org"')
        ->toContain('alt="Ein Schloss"')->toContain('Foto: Beispiel')->toContain($article->published_at->format('d.m.Y'));
});

it('strips raw html and unsafe links from article texts', function () {
    $article = ppArticle(['body' => "<script>alert(1)</script>Text <b>fett</b> [böse](javascript:alert(1))"]);

    $html = $this->get(route('articles.public.show', $article->slug))->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')->not->toContain('javascript:alert')->toContain('Text');
});

it('hides drafts and future articles from the public and shows them to editors as preview', function () {
    $draft = ppArticle(['status' => Article::STATUS_DRAFT, 'title' => 'Geheimer Entwurf']);
    $future = ppArticle(['published_at' => now()->addDay(), 'title' => 'Bald da']);

    $this->get(route('articles.public.show', $draft->slug))->assertNotFound();
    $this->get(route('articles.public.show', $future->slug))->assertNotFound();
    $this->get(route('articles.public.show', 'gibt-es-nicht'))->assertNotFound();

    $editor = obEditor();
    $this->actingAs($editor)->get(route('articles.public.show', $draft->slug))->assertOk()->assertSee('Vorschau')->assertSee('Geheimer Entwurf');
});

function obEditor(): User
{
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\RoleSeeder::class]);
    $user = User::factory()->create();
    \App\Modules\AccessGovernance\Models\RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => \App\Modules\AccessGovernance\Models\Role::query()->where('key', 'administration_staff')->value('id'),
        'source' => \App\Modules\AccessGovernance\Enums\RoleAssignmentSource::Manual,
        'starts_at' => now()->subMinute(),
    ]);

    return $user;
}

it('delivers the article image only for visible articles', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    \Illuminate\Support\Facades\Storage::disk('local')->put('articles/bild.jpg', 'JPEGDATEN');
    $visible = ppArticle(['image_path' => 'articles/bild.jpg', 'image_alt' => 'Bild', 'image_credit' => 'Foto']);
    $draft = ppArticle(['image_path' => 'articles/bild.jpg', 'image_alt' => 'Bild', 'status' => Article::STATUS_DRAFT]);
    $without = ppArticle();

    $this->get(route('articles.public.image', $visible->slug))->assertOk();
    $this->get(route('articles.public.image', $draft->slug))->assertNotFound();
    $this->get(route('articles.public.image', $without->slug))->assertNotFound();
});
