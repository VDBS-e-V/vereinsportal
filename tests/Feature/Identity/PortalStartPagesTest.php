<?php

use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

it('serves the start page access application and contact form publicly', function () {
    $this
        ->get('http://my.vdb.test/')
        ->assertOk()
        ->assertSeeText('Willkommen im VDBS Serviceportal')
        ->assertSeeText('Das VDBS Serviceportal')
        ->assertSeeText('Empfohlene Artikel')
        ->assertSeeText('Zugang zum Portal')
        ->assertSeeText('Kontakt');

    $this
        ->get('http://my.vdb.test/ueber-das-portal')
        ->assertOk()
        ->assertSeeText('Unser Service-Portal')
        ->assertSeeText('Smart. Vernetzt. Engagiert.')
        ->assertSeeText('Häufig gestellte Fragen')
        ->assertSeeText('Zugang zum Portal');

    $this
        ->get('http://my.vdb.test/faq')
        ->assertOk()
        ->assertSeeText('Unser Service-Portal')
        ->assertSeeText('Häufig gestellte Fragen')
        ->assertSeeText('Wo finde ich Hilfe, wenn ich Probleme mit dem Portal habe?');

    $this
        ->get('http://my.vdb.test/zugang-zum-portal')
        ->assertOk()
        ->assertSeeText('Antrag - Zugang zum Portal')
        ->assertSeeText('1. Verbindung')
        ->assertSeeText('2. Kontaktdaten')
        ->assertSeeText('3. ToS & Prüfen');

    $this
        ->get('http://my.vdb.test/kontakt')
        ->assertOk()
        ->assertSeeText('Kontaktformular')
        ->assertSeeText('Empfänger')
        ->assertSeeText('Betreff')
        ->assertSeeText('Inhalt');
});

it('keeps personal account pages protected while the start page stays public', function () {
    $this
        ->get('http://my.vdb.test/')
        ->assertOk();

    $this
        ->get('http://my.vdb.test/konto')
        ->assertRedirect('http://my.vdb.test/anmelden');
});

it('walks through and submits the portal access application', function () {
    Mail::fake();

    Volt::test('portal.access')
        ->set('connection', 'Teamer:in')
        ->set('organisation', 'Beispielschule')
        ->set('details', 'Teamer im Projekt')
        ->call('continueToContact')
        ->assertSet('step', 2)
        ->set('first_name', 'Erika')
        ->set('last_name', 'Musterfrau')
        ->set('email', 'erika@example.test')
        ->set('birth_date', '2000-01-01')
        ->set('phone', '+49 30 123456')
        ->set('preferred_username', 'erika.musterfrau')
        ->call('review')
        ->assertSet('step', 3)
        ->assertSeeText('Prüfen Sie ihre Angaben')
        ->set('terms_accepted', true)
        ->call('submitApplication')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSeeText('Antrag abgesendet');
});

it('submits the contact form shown in the mockup', function () {
    Mail::fake();

    Volt::test('portal.contact')
        ->set('first_name', 'Max')
        ->set('last_name', 'Mustermann')
        ->set('email', 'max.mustermann@example.test')
        ->set('recipient', 'support')
        ->set('subject', 'Frage zum Portal')
        ->set('content', 'Bitte helfen Sie mir beim Zugang.')
        ->set('privacy_accepted', true)
        ->call('sendMessage')
        ->assertHasNoErrors()
        ->assertSet('sent', true)
        ->assertSeeText('Ihre Nachricht wurde versendet');
});
