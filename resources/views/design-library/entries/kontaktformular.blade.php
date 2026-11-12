<p>Muster: längeres Formular mit Raster, Auswahl, Textarea mit Zähler, Upload und Einwilligung.</p>

<x-vdbs.forms.form action="#" :files="true" :summary="false">
    <x-vdbs.forms.form-grid :columns="2">
        <x-vdbs.forms.input name="k_name" label="Name" :required="true" autocomplete="name" />
        <x-vdbs.forms.input name="k_mail" label="E-Mail" type="email" :required="true" autocomplete="email" />
        <x-vdbs.forms.select name="k_topic" label="Anliegen" placeholder="Bitte wählen" :required="true" class="vdbs-form-grid__full" :options="['support' => 'Technischer Support', 'member' => 'Mitgliedschaft', 'other' => 'Sonstiges']" />
        <x-vdbs.forms.textarea name="k_text" label="Nachricht" :required="true" maxlength="1000" :counter="true" class="vdbs-form-grid__full" hint="Bitte beschreiben Sie Ihr Anliegen." />
        <x-vdbs.forms.file name="k_file" label="Anhang" :optional="true" accept=".pdf,image/*" hint="PDF oder Bild, höchstens 5 MB." class="vdbs-form-grid__full" />
    </x-vdbs.forms.form-grid>
    <x-vdbs.forms.checkbox name="k_accept" label="Ich stimme der Verarbeitung meiner Daten zu" :required="true" />
    <x-slot:actions>
        <x-vdbs.actions.button type="submit" icon="send" size="l">Nachricht senden</x-vdbs.actions.button>
    </x-slot:actions>
</x-vdbs.forms.form>
