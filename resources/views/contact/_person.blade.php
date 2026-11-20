{{-- Angaben zur Person (Schritt 2; bei Angemeldeten Teil von Schritt 3, vorbelegt aus dem Konto). Erwartet $form und $prefill. --}}
    <x-vdbs.forms.form-grid :columns="2">
        <x-vdbs.forms.input name="first_name" label="Vorname" :required="true" :value="$prefill['first_name'] ?? null" placeholder="Max" autocomplete="given-name" />
        <x-vdbs.forms.input name="last_name" label="Nachname" :required="true" :value="$prefill['last_name'] ?? null" placeholder="Mustermann" autocomplete="family-name" />
    </x-vdbs.forms.form-grid>
    <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" :value="$prefill['email'] ?? null" placeholder="max.mustermann@beispiel.example" autocomplete="email" hint="Dorthin senden wir die Antwort." />
    @if ($form->ask_phone !== 'no')
        <x-vdbs.forms.input name="phone" label="Telefon" type="tel" :value="$prefill['phone'] ?? null" :required="$form->ask_phone === 'required'" :optional="$form->ask_phone === 'optional'" autocomplete="tel" />
    @endif
    @if ($form->ask_organization !== 'no')
        <x-vdbs.forms.input name="organization" label="Schule oder Organisation" :required="$form->ask_organization === 'required'" :optional="$form->ask_organization === 'optional'" maxlength="150" autocomplete="organization" />
    @endif
