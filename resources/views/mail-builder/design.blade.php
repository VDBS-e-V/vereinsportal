@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Systemmails', 'href' => route('mailbuilder.system.index')], ['label' => 'Mail-Design']];
    $fontLabels = ['sans' => 'Arial (serifenlos)', 'serif' => 'Georgia (mit Serifen)', 'verdana' => 'Verdana (weit)'];
    $logoOptions = $assets->mapWithKeys(fn ($asset) => [$asset->id => $asset->original_name])->all();
@endphp

<x-layouts.app title="Mail-Design" width="wide">
    <x-vdbs.layout.page-header title="Mail-Design" lead="Farben, Schrift, Logo und Fußzeile, die für alle Systemmails gelten. Die Vorschau rechts zeigt jede Änderung sofort." :breadcrumb="$crumbs" />

    <div class="mb mb--design" data-mb-design data-preview-url="{{ route('mailbuilder.design.preview') }}" data-csrf="{{ csrf_token() }}">
        <x-vdbs.forms.form :action="route('mailbuilder.design.update')" method="PUT" id="mb-design-form">
            <x-vdbs.forms.form-section title="Farben" description="Farben als sechsstelliger Code, z. B. #057f5b. Das Portal warnt bei zu schwachem Kontrast.">
                <x-vdbs.forms.form-grid :columns="2">
                    @foreach ($colors as $key => $label)
                        <x-vdbs.forms.input :name="$key" :label="$label" maxlength="7" :value="old($key, $settings[$key])" pattern="#[0-9a-fA-F]{6}" />
                    @endforeach
                </x-vdbs.forms.form-grid>
            </x-vdbs.forms.form-section>

            <x-vdbs.forms.form-section title="Schrift und Form">
                <x-vdbs.forms.form-grid :columns="2">
                    <x-vdbs.forms.select name="font" label="Schrift" :options="collect($fonts)->mapWithKeys(fn ($f) => [$f => $fontLabels[$f] ?? $f])->all()" :value="old('font', $settings['font'])" hint="Nur Schriften, die jedes Mailprogramm kennt." />
                    <x-vdbs.forms.input name="width" label="Breite in Pixel" type="number" :value="old('width', $settings['width'])" hint="480 bis 700, üblich sind 600." />
                    <x-vdbs.forms.input name="radius" label="Eckenrundung in Pixel" type="number" :value="old('radius', $settings['radius'])" hint="0 bis 16." />
                </x-vdbs.forms.form-grid>
            </x-vdbs.forms.form-section>

            <x-vdbs.forms.form-section title="Kopf" description="Ein Logo (aus den Mailbildern) oder ein Kopftext. Ohne beides bleibt der Kopf leer.">
                <x-vdbs.forms.form-grid :columns="2">
                    <x-vdbs.forms.select name="logo_asset_id" label="Logo" placeholder="Kein Logo" :optional="true" :options="$logoOptions" :value="old('logo_asset_id', $settings['logo_asset_id'])" hint="Bilder laden Sie unter „Bilder“ hoch." />
                    <x-vdbs.forms.input name="logo_width" label="Breite des Logos in Pixel" type="number" :value="old('logo_width', $settings['logo_width'])" />
                    <x-vdbs.forms.input name="logo_alt" label="Alternativtext des Logos" maxlength="200" :value="old('logo_alt', $settings['logo_alt'])" />
                    <x-vdbs.forms.input name="header_text" label="Kopftext (ohne Logo)" :optional="true" maxlength="200" :value="old('header_text', $settings['header_text'])" />
                </x-vdbs.forms.form-grid>
            </x-vdbs.forms.form-section>

            <x-vdbs.forms.form-section title="Fußzeile" description="Steht unter jeder Mail. Platzhalter wie {verein} und {portal} sind möglich.">
                <x-vdbs.forms.textarea name="footer_html" label="Text der Fußzeile" :rich="true" :optional="true" :value="old('footer_html', $settings['footer_html'])" />
            </x-vdbs.forms.form-section>

            <x-slot:actions>
                <x-vdbs.actions.button type="submit" icon="check">Design speichern</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="secondary" :href="route('mailbuilder.system.index')">Zurück</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>

        <div class="mb__panel mb__preview">
            <h2 class="mb__title">Vorschau</h2>
            <div class="mb__preview-bar">
                <x-vdbs.actions.button variant="secondary" size="s" data-mb-dark>Dunkelmodus</x-vdbs.actions.button>
            </div>
            <ul class="mb__issues" data-mb-issues></ul>
            <iframe class="mb__preview-frame" title="Vorschau des Mail-Designs" data-mb-frame sandbox=""></iframe>
        </div>
    </div>
</x-layouts.app>
