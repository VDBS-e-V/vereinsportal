@php
    $contact = config('portal.home.contact');
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Kontakt']];
@endphp

<x-layouts.app title="Kontakt" width="medium">
    <x-vdbs.layout.page-header title="Kontaktformular" :lead="$contact['text']" :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('portal.contact.send')">
        <x-vdbs.forms.bot-guard />
        <x-vdbs.forms.form-section title="Ihre Nachricht">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="first_name" label="Vorname" :required="true" placeholder="Max" autocomplete="given-name" />
                <x-vdbs.forms.input name="last_name" label="Nachname" :required="true" placeholder="Mustermann" autocomplete="family-name" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" placeholder="max.mustermann@beispiel.example" autocomplete="email" hint="Dorthin senden wir die Antwort." />
            <x-vdbs.forms.select name="recipient" label="Empfänger" placeholder="-- Bitte eine Option wählen --" :required="true" :options="$recipients" />
            <x-vdbs.forms.input name="subject" label="Betreff" :required="true" maxlength="150" placeholder="Geben Sie Ihrem Anliegen einen Betreff" />
            <x-vdbs.forms.textarea name="message" label="Inhalt" :required="true" maxlength="5000" :counter="true" :rows="6" placeholder="Ihre Nachricht …" />
            <x-vdbs.forms.checkbox name="consent" :required="true" label="Ich willige ein, dass meine Daten zum Zweck der Kontaktaufnahme gespeichert und verarbeitet werden. Ich kann diese Einwilligung jederzeit widerrufen. Weitere Informationen finden Sie in unserer Datenschutzerklärung (siehe Fußzeile)." />
        </x-vdbs.forms.form-section>

        {{-- Köder für Programme: Menschen sehen und füllen das Feld nicht aus. --}}
        <div style="position:absolute;left:-10000px" aria-hidden="true">
            <label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label>
        </div>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="send">Absenden</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if (($contact['lines'] ?? []) !== [])
        <div style="margin-top:var(--space-8)">
            <x-vdbs.cards.contact-block :title="$contact['title']" :lines="$contact['lines']" :level="2" />
        </div>
    @endif
</x-layouts.app>
