@props(['i', 'row' => [], 'primary' => false])

{{-- Eine Zeile "Ansprechperson" im Schulformular. $i ist der Index (oder __INDEX__ in der Vorlage für „Neue hinzufügen“). --}}
<fieldset data-vdbs-repeater-row style="border:0;padding:0;margin:0 0 var(--space-5);border-top:1px solid var(--border-default);padding-top:var(--space-4)">
    <legend class="vdbs-sr-only">Ansprechperson</legend>
    <x-vdbs.forms.form-grid :columns="2">
        <x-vdbs.forms.input :id="'contact-'.$i.'-name'" :name="'contacts['.$i.'][name]'" label="Name" :optional="true" :value="$row['name'] ?? null" />
        <x-vdbs.forms.input :id="'contact-'.$i.'-role'" :name="'contacts['.$i.'][role]'" label="Funktion" :optional="true" :value="$row['role'] ?? null" hint="z. B. Schulleitung, Sekretariat, Bibliothek" />
        <x-vdbs.forms.input :id="'contact-'.$i.'-email'" :name="'contacts['.$i.'][email]'" label="E-Mail-Adresse" type="email" :optional="true" :value="$row['email'] ?? null" />
        <x-vdbs.forms.input :id="'contact-'.$i.'-phone'" :name="'contacts['.$i.'][phone]'" label="Telefon" type="tel" :optional="true" :value="$row['phone'] ?? null" />
    </x-vdbs.forms.form-grid>
    <div style="display:flex;gap:var(--space-5);align-items:center;flex-wrap:wrap">
        <label style="display:inline-flex;gap:var(--space-2);align-items:center"><input type="radio" name="primary_contact" value="{{ $i }}" @checked($primary)> Hauptansprechperson</label>
        <button type="button" class="vdbs-btn vdbs-btn--tertiary vdbs-btn--shape-line vdbs-btn--size-s vdbs-js-only" data-vdbs-repeater-remove>Zeile entfernen</button>
    </div>
</fieldset>
