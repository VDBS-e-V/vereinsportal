@php
    $custom = $form->customFields();
    $steps = ['Thema wählen', 'Ihre Daten'];

    if ($form->isMessage()) {
        $steps[] = 'Ihre Anfrage';
    } elseif ($custom !== []) {
        $steps[] = 'Weitere Angaben';
    }

    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Kontakt', 'href' => route('portal.contact')], ['label' => $form->name]];
    $lead = filled($form->description) ? $form->description : 'Bitte geben Sie Ihre Daten ein, wir melden uns bei Ihnen.';
    $section = $form->isMessage() ? 'Ihre Anfrage' : 'Weitere Angaben';
    $number = 3;
@endphp

<x-layouts.app :title="$form->name" width="medium">
    <x-vdbs.layout.page-header :title="$form->name" :lead="$lead" :breadcrumb="$crumbs" />

    <x-vdbs.navigation.stepper :steps="$steps" :current="2" label="Ablauf der Kontaktaufnahme" />

    @if ($topic)
        <p style="margin-top:var(--space-5)">Ihr Thema: <strong>{{ $topic->title }}</strong> · <a href="{{ route('portal.contact') }}">Thema ändern</a></p>
    @endif

    <x-vdbs.forms.form :action="route('portal.contact.send', ['form' => $form->slug])">
        <x-vdbs.forms.bot-guard />
        <input type="hidden" name="thema" value="{{ $topic?->id }}">

        <x-vdbs.forms.form-section title="Schritt 2: Ihre Daten">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="first_name" label="Vorname" :required="true" placeholder="Max" autocomplete="given-name" />
                <x-vdbs.forms.input name="last_name" label="Nachname" :required="true" placeholder="Mustermann" autocomplete="family-name" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" placeholder="max.mustermann@beispiel.example" autocomplete="email" hint="Dorthin senden wir die Antwort." />
            @if ($form->ask_phone !== 'no')
                <x-vdbs.forms.input name="phone" label="Telefon" type="tel" :required="$form->ask_phone === 'required'" :optional="$form->ask_phone === 'optional'" autocomplete="tel" />
            @endif
            @if ($form->ask_organization !== 'no')
                <x-vdbs.forms.input name="organization" label="Schule oder Organisation" :required="$form->ask_organization === 'required'" :optional="$form->ask_organization === 'optional'" maxlength="150" autocomplete="organization" />
            @endif
        </x-vdbs.forms.form-section>

        @if ($form->isMessage() || $custom !== [])
            <x-vdbs.forms.form-section :title="'Schritt '.$number.': '.$section">
                @foreach ($custom as $field)
                    @php
                        $name = 'answers['.$field['key'].']';
                        $id = 'answer-'.$field['key'];
                        $required = (bool) $field['required'];
                    @endphp
                    @switch($field['type'])
                        @case('textarea')
                            <x-vdbs.forms.textarea :id="$id" :name="$name" :label="$field['label']" :required="$required" :optional="! $required" :hint="$field['hint']" :rows="4" maxlength="5000" />
                            @break
                        @case('select')
                            <x-vdbs.forms.select :id="$id" :name="$name" :label="$field['label']" :required="$required" :optional="! $required" :hint="$field['hint']" placeholder="-- Bitte wählen --" :options="array_combine($field['options'], $field['options'])" />
                            @break
                        @case('checkbox')
                            <x-vdbs.forms.checkbox :id="$id" :name="$name" :label="$field['label']" :required="$required" :hint="$field['hint']" />
                            @break
                        @default
                            <x-vdbs.forms.input :id="$id" :name="$name" :label="$field['label']" :type="in_array($field['type'], ['email', 'tel', 'date'], true) ? $field['type'] : 'text'" :required="$required" :optional="! $required" :hint="$field['hint']" />
                    @endswitch
                @endforeach

                @if ($form->isMessage())
                    <x-vdbs.forms.input name="subject" label="Betreff" :required="true" maxlength="150" placeholder="Geben Sie Ihrem Anliegen einen Betreff" :value="$topic?->title" />
                    <x-vdbs.forms.textarea name="message" label="Inhalt" :required="true" maxlength="5000" :counter="true" :rows="6" placeholder="Ihre Nachricht …" />
                @endif
            </x-vdbs.forms.form-section>
        @endif

        <x-vdbs.forms.checkbox name="consent" :required="true" label="Ich willige ein, dass meine Daten zum Zweck der Kontaktaufnahme gespeichert und verarbeitet werden. Ich kann diese Einwilligung jederzeit widerrufen. Weitere Informationen finden Sie in unserer Datenschutzerklärung (siehe Fußzeile)." />

        {{-- Köder für Programme: Menschen sehen und füllen das Feld nicht aus. --}}
        <div style="position:absolute;left:-10000px" aria-hidden="true">
            <label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label>
        </div>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="send">Absenden</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('portal.contact')">Anderes Thema wählen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
