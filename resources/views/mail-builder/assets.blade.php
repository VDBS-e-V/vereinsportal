@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Systemmails', 'href' => route('mailbuilder.system.index')], ['label' => 'Bilder']];
@endphp

<x-layouts.app title="Mailbilder" width="wide">
    <x-vdbs.layout.page-header title="Bilder für Mails" :lead="'Logo und Bilder für den Mail-Builder. Erlaubt sind PNG, JPG und GIF bis '.($maxKb / 1024).' MB; SVG zeigen viele Mailprogramme nicht an.'" :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('mailbuilder.assets.store')" :files="true">
        <x-vdbs.forms.form-section title="Bild hochladen">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.file name="file" label="Bilddatei" accept="image/png,image/jpeg,image/gif" :required="true" />
                <x-vdbs.forms.input name="alt" label="Vorgeschlagener Alternativtext" :optional="true" maxlength="200" hint="Beschreibt das Bild für Screenreader und wenn Bilder blockiert sind." />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>
        <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Hochladen</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>

    <x-vdbs.layout.section title="Vorhandene Bilder">
        @if ($assets->isEmpty())
            <x-vdbs.feedback.empty-state title="Noch keine Bilder" icon="mail"><p>Laden Sie oben das erste Bild hoch.</p></x-vdbs.feedback.empty-state>
        @else
            <div class="mb__swatches">
                @foreach ($assets as $asset)
                    <div class="mb__asset">
                        <img src="{{ $asset->url() }}" alt="{{ $asset->alt_default ?: $asset->original_name }}">
                        <strong>{{ $asset->original_name }}</strong>
                        <span class="mb__hint">{{ $asset->width }} × {{ $asset->height }} Pixel, {{ number_format($asset->size / 1024, 0, ',', '.') }} KB</span>
                        <form method="POST" action="{{ route('mailbuilder.assets.destroy', $asset) }}">
                            @csrf
                            @method('DELETE')
                            <x-vdbs.actions.button type="submit" variant="secondary" size="s">Löschen</x-vdbs.actions.button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-vdbs.layout.section>
</x-layouts.app>
