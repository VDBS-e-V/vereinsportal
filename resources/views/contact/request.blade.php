@php
    $custom = $form->customFields();
    $steps = ['Thema wählen', 'Ihre Daten', $form->isMessage() ? 'Ihre Anfrage' : 'Weitere Angaben'];
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Kontakt', 'href' => route('portal.contact')], ['label' => $form->name]];
    $section = $form->isMessage() ? 'Ihre Anfrage' : 'Weitere Angaben';
@endphp

<x-layouts.app :title="$form->name" width="medium">
    <x-vdbs.layout.page-header :title="$form->name" lead="Fast geschafft: Noch Ihre Anfrage, dann können Sie absenden." :breadcrumb="$crumbs" />

    <x-vdbs.navigation.stepper :steps="$steps" :current="3" label="Ablauf der Kontaktaufnahme" />

    <p style="margin-top:var(--space-5)">
        @if ($topic)Ihr Thema: <strong>{{ $topic->title }}</strong> · @endif
        Ihre Angaben: <strong>{{ $person['first_name'] }} {{ $person['last_name'] }}</strong> ({{ $person['email'] }}) · <a href="{{ route('portal.contact.form', ['form' => $form->slug, 'thema' => $topic?->id]) }}">Angaben ändern</a>
    </p>

    <x-vdbs.forms.form :action="route('portal.contact.send', ['form' => $form->slug])">
        <x-vdbs.forms.bot-guard />

        <x-vdbs.forms.form-section :title="'Schritt 3: '.$section">
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

        <x-vdbs.forms.checkbox name="consent" :required="true" label="Ich willige ein, dass meine Daten zum Zweck der Kontaktaufnahme gespeichert und verarbeitet werden. Ich kann diese Einwilligung jederzeit widerrufen. Weitere Informationen finden Sie in unserer Datenschutzerklärung (siehe Fußzeile)." />

        {{-- Köder für Programme: Menschen sehen und füllen das Feld nicht aus. --}}
        <div style="position:absolute;left:-10000px" aria-hidden="true">
            <label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label>
        </div>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="send">Absenden</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('portal.contact.form', ['form' => $form->slug, 'thema' => $topic?->id])">Zurück zu Schritt 2</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
