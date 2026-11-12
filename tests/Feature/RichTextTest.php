<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Articles\Models\Article;
use App\Modules\Events\Mail\EventMessageMail;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Services\EventAdministration;
use App\Modules\Pages\Models\Page;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Models\Seminar;
use App\Support\Html\RichText;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function rtUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

// ---------- Bereinigung ----------

it('keeps the allowed formatting and umlauts', function () {
    expect(RichText::sanitize('<p>Hallo <strong>Welt</strong> äöü &amp; ok</p><h2>Titel</h2><ul><li>a</li></ul><blockquote>Zitat</blockquote><hr>'))
        ->toBe('<p>Hallo <strong>Welt</strong> äöü &amp; ok</p><h2>Titel</h2><ul><li>a</li></ul><blockquote>Zitat</blockquote><hr>');
});

it('removes scripts, event handlers, styles, frames, images and unknown tags', function () {
    $clean = RichText::sanitize('<p onclick="x()" style="color:red">Text<script>alert(1)</script><b>fett</b> <i>kursiv</i><img src=x onerror=alert(1)></p><iframe src="evil"></iframe><style>p{}</style><div><span>Span</span></div><table><tr><td>Zelle</td></tr></table><h1>Eins</h1>');

    expect($clean)->toBe('<p>Text<strong>fett</strong> <em>kursiv</em></p>Span'.'Zelle<h2>Eins</h2>')
        ->and($clean)->not->toContain('script')->not->toContain('onclick')->not->toContain('style')->not->toContain('iframe')->not->toContain('<img');
});

it('keeps safe links and drops dangerous ones, also with hidden control characters', function () {
    $clean = RichText::sanitize('<p><a href="javascript:alert(1)">a</a> <a href="https://example.org/x?y=1" target="_blank" onclick="x">b</a> <a href="jav&#x09;ascript:alert(1)">c</a> <a href="mailto:a@example.org">d</a> <a href="data:text/html;base64,AAAA">e</a> <a href="/intern">f</a></p>');

    expect($clean)->toContain('<a href="https://example.org/x?y=1" rel="noopener noreferrer">b</a>')->toContain('<a href="mailto:a@example.org" rel="noopener noreferrer">d</a>')->toContain('<a href="/intern" rel="noopener noreferrer">f</a>')
        ->and($clean)->not->toContain('javascript')->not->toContain('data:')->not->toContain('target=')->not->toContain('onclick');
});

it('recognizes empty editor output and treats old texts as markdown or plain text', function () {
    expect(RichText::isEmpty('<p></p>'))->toBeTrue()->and(RichText::isEmpty('<p>&nbsp;</p>'))->toBeTrue()->and(RichText::isEmpty('  '))->toBeTrue()->and(RichText::isEmpty('<p>Text</p>'))->toBeFalse()
        ->and(RichText::clean('<p></p>'))->toBeNull()->and(RichText::normalize('<p></p>'))->toBe('')
        ->and(RichText::clean("  Einfacher Text  \n"))->toBe('Einfacher Text')
        ->and(RichText::clean('<p onclick="x">Hallo</p>'))->toBe('<p>Hallo</p>');

    expect((string) rich_text("Erste Zeile\nZweite Zeile\n\n**fett**"))->toContain('<br />')->toContain('<strong>fett</strong>')
        ->and((string) rich_text('Text <script>alert(1)</script> [x](javascript:alert(1))'))->not->toContain('<script>')->not->toContain('javascript:')
        ->and((string) rich_text(null))->toBe('');
});

it('makes plain text of editor html for excerpts', function () {
    expect(RichText::toPlain('<p>Erster Absatz</p><p>Zweiter&nbsp;Absatz mit <strong>fett</strong></p>'))->toBe('Erster Absatz Zweiter Absatz mit fett')
        ->and(RichText::toPlain('<p>'.str_repeat('wort ', 100).'</p>', 20))->toEndWith('...');
});

// ---------- Felder ----------

it('puts the editor on the content fields and nowhere on plain fields', function () {
    $staff = rtUser(RoleKey::AdministrationStaff);
    $coordination = rtUser(RoleKey::Coordination);
    $partner = Partner::factory()->create();

    $this->actingAs($staff)->get(route('articles.admin.create'))->assertOk()->assertSee('data-vdbs-richtext=', false);
    $this->actingAs($staff)->get(route('pages.admin.edit', 'datenschutz'))->assertOk()->assertSee('data-vdbs-richtext=', false)->assertSee(url('vendor/tinymce'), false);
    $this->actingAs($coordination)->get(route('events.admin.create'))->assertOk()->assertSee('data-vdbs-richtext=', false);
    $this->actingAs($coordination)->get(route('seminars.create'))->assertOk()->assertSee('data-vdbs-richtext=', false);
    $this->actingAs($coordination)->get(route('partners.edit', $partner))->assertOk()->assertSee('data-vdbs-richtext=', false);

    $this->get(route('portal.contact'))->assertOk()->assertDontSee('data-vdbs-richtext', false);
});

it('shows legacy markdown in the editor as html', function () {
    $staff = rtUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->get(route('pages.admin.edit', 'datenschutz'))->assertOk()->assertSee('&lt;h2&gt;', false)->assertDontSee('## Verantwortlicher', false);
});

it('saves articles from the editor sanitized and shows them formatted', function () {
    $staff = rtUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->post(route('articles.admin.store'), ['title' => 'Mit Editor', 'category' => 'Themenartikel', 'body' => '<p onclick="x">Hallo <strong>Welt</strong><script>alert(1)</script></p><ul><li>Punkt</li></ul>', 'status' => 'published', 'published_at' => now()->subHour()->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();

    $article = Article::query()->where('title', 'Mit Editor')->firstOrFail();
    expect($article->body)->toBe('<p>Hallo <strong>Welt</strong></p><ul><li>Punkt</li></ul>');

    $this->get(route('articles.public.show', $article->slug))->assertOk()->assertSee('<strong>Welt</strong>', false)->assertSee('<li>Punkt</li>', false)->assertDontSee('alert(1)', false);
    expect($article->excerpt())->toBe('Hallo Welt Punkt');
});

it('refuses an empty editor text where text is required', function () {
    $staff = rtUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->post(route('articles.admin.store'), ['title' => 'Leer', 'body' => '<p></p>', 'status' => 'draft'])->assertSessionHasErrors('body');
    $this->actingAs($staff)->put(route('pages.admin.update', 'impressum'), ['body' => '<p>&nbsp;</p>'])->assertSessionHasErrors('body');
    expect(Article::query()->count())->toBe(0);
});

it('saves legal pages from the editor and keeps old markdown pages working', function () {
    $staff = rtUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->put(route('pages.admin.update', 'datenschutz'), ['body' => '<h2>Verantwortlicher</h2><p>Unser <b>Verein</b> <a href="javascript:alert(1)">böse</a></p>'])->assertSessionHasNoErrors();
    expect(Page::query()->where('key', 'datenschutz')->value('body'))->toBe('<h2>Verantwortlicher</h2><p>Unser <strong>Verein</strong> böse</p>');

    $this->get('/datenschutz')->assertOk()->assertSee('<h2>Verantwortlicher</h2>', false)->assertSee('<strong>Verein</strong>', false)->assertDontSee('javascript:', false);
    $this->get('/impressum')->assertOk()->assertSee('Vertretung');
});

it('shows event descriptions formatted and as plain text in the list', function () {
    $coordination = rtUser(RoleKey::Coordination);

    $this->actingAs($coordination)->post(route('events.admin.store'), ['title' => 'Demokratietag', 'status' => 'open', 'description' => '<p>Ein <strong>toller</strong> Tag<script>x</script></p>'])->assertSessionHasNoErrors();
    $event = Event::query()->firstOrFail();

    expect($event->description)->toBe('<p>Ein <strong>toller</strong> Tag</p>');

    $this->get(route('events.public.show', $event->slug))->assertOk()->assertSee('<strong>toller</strong>', false);
    $this->get(route('events.public.index'))->assertOk()->assertSee('Ein toller Tag')->assertDontSee('&lt;p&gt;', false);

    $this->actingAs($coordination)->put(route('events.admin.update', $event), ['title' => 'Demokratietag', 'status' => 'open', 'description' => '<p></p>'])->assertSessionHasNoErrors();
    expect($event->fresh()->description)->toBeNull();
});

it('sends event messages as formatted and sanitized mail', function () {
    $coordination = rtUser(RoleKey::Coordination);
    $event = app(EventAdministration::class)->create(['title' => 'Demokratietag', 'status' => 'open'], $coordination);
    $event->registrations()->create(['name' => 'A', 'school' => 'S', 'class_name' => '9', 'birth_date' => '2010-01-01', 'email' => 'a@example.org', 'ticket_code' => 'RTXA12345', 'verified_at' => now()]);

    $this->actingAs($coordination)->post(route('events.admin.messages.store', $event), ['audience' => 'confirmed', 'subject' => 'Hinweis', 'body' => '<p>Bitte <strong>pünktlich</strong> sein.<script>alert(1)</script></p>'])->assertSessionHasNoErrors();

    Mail::assertSent(EventMessageMail::class, function (EventMessageMail $mail): bool {
        $html = $mail->render();

        return str_contains($html, '<strong>pünktlich</strong>') && ! str_contains($html, '<script>alert');
    });

    $this->actingAs($coordination)->post(route('events.admin.messages.store', $event), ['audience' => 'confirmed', 'subject' => 'Leer', 'body' => '<p>&nbsp;</p>'])->assertSessionHasErrors('body');
});

it('shows seminar and school notes formatted and exports plain text', function () {
    $coordination = rtUser(RoleKey::Coordination);
    $partner = Partner::factory()->create(['name' => 'Rosa-Luxemburg-Schule']);

    $this->actingAs($coordination)->post(route('seminars.store'), ['title' => 'SV-Tage Herbst', 'type' => 'sv_days', 'partner_id' => $partner->id, 'notes' => '<p>Erstes Gespräch <em>im Mai</em></p><script>x</script>'])->assertSessionHasNoErrors();
    $seminar = Seminar::query()->firstOrFail();

    expect($seminar->notes)->toBe('<p>Erstes Gespräch <em>im Mai</em></p>');
    $this->actingAs($coordination)->get(route('seminars.show', $seminar))->assertOk()->assertSee('<em>im Mai</em>', false);

    $this->actingAs($coordination)->put(route('partners.update', $partner), ['name' => 'Rosa-Luxemburg-Schule', 'status' => 'active', 'notes' => '<p>Gute <strong>Zusammenarbeit</strong></p>'])->assertSessionHasNoErrors();
    $this->actingAs($coordination)->get(route('partners.show', $partner))->assertSee('<strong>Zusammenarbeit</strong>', false);

    $csv = $this->actingAs($coordination)->get(route('partners.export'))->assertOk()->streamedContent();
    expect($csv)->toContain('Gute Zusammenarbeit')->not->toContain('<strong>');
});
