<?php

use App\Modules\Access\Mail\PasswordResetMail;
use App\Modules\AccessRequests\Mail\AccessRequestRejectedMail;
use App\Modules\ContactForms\Mail\ContactFormMail;
use App\Modules\MailBuilder\Services\SystemMailRenderer;
use App\Modules\MailBuilder\Support\MailCheck;
use App\Modules\MailBuilder\Support\SystemMails;
use App\Modules\Mailings\Models\MailTemplate;
use App\Modules\Seminars\Mail\SeminarNoticeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function smOverride(string $key, array $blocks, string $subject = 'Eigener Betreff'): MailTemplate
{
    return MailTemplate::query()->create(['key' => 'sys-'.$key, 'kind' => 'system', 'name' => 'Änderung', 'subject' => $subject, 'body' => '', 'system_key' => $key, 'blocks' => ['version' => 1, 'preheader' => '', 'blocks' => $blocks], 'status' => 'published']);
}

it('renders every system mail with its default text and sample values without problems', function () {
    foreach (SystemMails::all() as $key => $mail) {
        $rendered = app(SystemMailRenderer::class)->render($key, $mail->samples());

        expect($rendered->subject)->not->toBe('')->and($rendered->subject)->not->toContain('{')
            ->and($rendered->html)->toContain('<!DOCTYPE html>')->and($rendered->html)->not->toContain('{anrede}')
            ->and(MailCheck::hasErrors($rendered->issues))->toBeFalse("Fehler im Standardtext von {$key}: ".json_encode($rendered->issues, JSON_UNESCAPED_UNICODE))
            ->and($rendered->text)->not->toBe('');

        foreach ($mail->protected() as $name) {
            expect(str_contains($rendered->html, 'href=') || str_contains($rendered->html, htmlspecialchars((string) $mail->samples()[$name])))->toBeTrue("{$key}: {{$name}} fehlt in der Mail");
        }
    }
});

it('has a register entry for every mail class that builds on the template base', function () {
    $keys = [];

    foreach (glob(app_path('Modules/*/Mail/*.php')) ?: [] as $file) {
        $source = (string) file_get_contents($file);

        if (str_contains($source, 'extends TemplatedMail') && preg_match('/function systemKey\(\): string\s*\{(.*?)\n    \}/s', $source, $body)) {
            preg_match_all('/\'([a-z_]+\.[a-z_]+)\'/', $body[1], $found);
            $keys = [...$keys, ...$found[1]];
        }
    }

    expect($keys)->not->toBeEmpty();

    foreach ($keys as $key) {
        expect(SystemMails::find($key))->not->toBeNull("Unbekannte Systemmail {$key}");
    }
});

it('sends the password reset mail through the builder with link, validity and a text version', function () {
    $mail = new PasswordResetMail('https://portal.example/reset/abc', 60);
    $html = $mail->render();

    expect($mail->envelope()->subject)->toBe('Passwort für das Vereinsportal zurücksetzen')
        ->and($html)->toContain('href="https://portal.example/reset/abc"')->toContain('Der Link ist 60 Minuten gültig')->toContain('Neues Passwort festlegen');

    $text = app(SystemMailRenderer::class)->render('access.password_reset', ['url' => 'https://portal.example/reset/abc', 'minuten' => '60'])->text;

    expect($text)->toContain('Neues Passwort festlegen: https://portal.example/reset/abc');
});

it('uses a stored change and falls back to the default when it is broken', function () {
    smOverride('access.password_reset', [['id' => 'a1', 'type' => 'text', 'props' => ['html' => '<p>Neuer Text mit {url} für {minuten} Minuten.</p>']]], 'Neuer Betreff');

    $mail = app(SystemMailRenderer::class)->render('access.password_reset', ['url' => 'https://portal.example/r', 'minuten' => '15']);

    expect($mail->subject)->toBe('Neuer Betreff')->and($mail->html)->toContain('Neuer Text mit https://portal.example/r für 15 Minuten.');

    MailTemplate::query()->where('system_key', 'access.password_reset')->update(['blocks' => ['version' => 1, 'preheader' => '', 'blocks' => [['id' => 'a1', 'type' => 'text', 'props' => ['html' => '<p>Ohne Link</p>']]]]]);

    $fallback = app(SystemMailRenderer::class)->render('access.password_reset', ['url' => 'https://portal.example/r', 'minuten' => '15']);

    expect($fallback->subject)->toBe('Passwort für das Vereinsportal zurücksetzen')->and($fallback->html)->toContain('href="https://portal.example/r"')->not->toContain('Ohne Link');
});

it('ignores drafts and unknown placeholders in a stored change', function () {
    $template = smOverride('access.account_deleted', [['id' => 'a1', 'type' => 'text', 'props' => ['html' => '<p>Entwurf</p>']]]);
    $template->update(['status' => 'draft']);

    expect(app(SystemMailRenderer::class)->render('access.account_deleted', [])->html)->not->toContain('Entwurf');

    $template->update(['status' => 'published', 'blocks' => ['version' => 1, 'preheader' => '', 'blocks' => [['id' => 'a1', 'type' => 'text', 'props' => ['html' => '<p>Hallo {tippfehler}</p>']]]]]);

    expect(app(SystemMailRenderer::class)->render('access.account_deleted', [])->html)->not->toContain('tippfehler');
});

it('shows the reason of a rejection only when there is one', function () {
    $with = (new AccessRequestRejectedMail('Anna Beispiel', 'Keine Zuordnung möglich.', 'https://portal.example/kontakt'))->render();
    $without = (new AccessRequestRejectedMail('Anna Beispiel', null, 'https://portal.example/kontakt'))->render();

    expect($with)->toContain('Guten Tag Anna Beispiel,')->toContain('Begründung: Keine Zuordnung möglich.')
        ->and($without)->not->toContain('Begründung:')->toContain('https://portal.example/kontakt');
});

it('keeps the contact form reply-to and distinguishes copy from original', function () {
    $person = ['first_name' => 'Erika', 'last_name' => 'Muster', 'email' => 'erika@example.org', 'phone' => '0123', 'organization' => 'Schule am Park'];
    $original = new ContactFormMail('Kontakt', 'Geschäftsstelle', 'Mitgliedschaft', $person, 'Frage', "Zeile 1\nZeile 2", [['label' => 'Anliegen', 'value' => 'Beitritt']]);
    $copy = new ContactFormMail('Kontakt', 'Geschäftsstelle', null, $person, null, 'Text', [], true);

    expect($original->envelope()->subject)->toBe('[Kontaktformular] Frage')->and($original->envelope()->replyTo)->toHaveCount(1)
        ->and($original->render())->toContain('eingegangen')->toContain('Zeile 1<br>Zeile 2')->toContain('Anliegen:')->toContain('Mit „Antworten“')
        ->and($copy->envelope()->subject)->toBe('Ihre Kopie: Kontakt')->and($copy->envelope()->replyTo)->toBe([])
        ->and($copy->render())->toContain('Guten Tag Erika,')->not->toContain('Mit „Antworten“');
});

it('renders seminar mails per occasion with escaped values and a link only when there is one', function () {
    $fields = ['nummer' => 'S-2027-0001', 'titel' => 'Medientag <b>', 'schule' => 'Schule am Park', 'termine' => '12.04.2027', 'leitung' => ''];
    $mail = new SeminarNoticeMail('team_confirmed', 'Anna', $fields, 'https://portal.example/teamende', 'Seminare öffnen');
    $html = $mail->render();

    expect($html)->toContain('Guten Tag Anna,')->toContain('Medientag &lt;b&gt;')->toContain('Seminare öffnen')->toContain('Anmeldung nötig')
        ->and($mail->subjectLine)->toBe('Zusage: Medientag <b>')
        ->and($mail->paragraphs)->toContain('Termine: 12.04.2027.')->not->toContain('Die Koordination hat Sie als Seminarleitung bestimmt.');

    $withoutLink = new SeminarNoticeMail('team_declined', null, $fields);

    expect($withoutLink->render())->not->toContain('Anmeldung nötig')->and($withoutLink->subjectLine)->toBe('Absage: Medientag <b>')->and($withoutLink->paragraphs)->toHaveCount(1);
});

it('lets the office change the text of a single seminar occasion', function () {
    smOverride('seminar.team_declined', [['id' => 'a1', 'type' => 'text', 'props' => ['html' => '<p>Diesmal leider nicht: {titel}.</p>']]], 'Schade: {titel}');

    $mail = new SeminarNoticeMail('team_declined', 'Anna', ['nummer' => 'S-1', 'titel' => 'Medientag', 'schule' => 'Schule']);

    expect($mail->subjectLine)->toBe('Schade: Medientag')->and($mail->paragraphs)->toBe(['Diesmal leider nicht: Medientag.']);
});
