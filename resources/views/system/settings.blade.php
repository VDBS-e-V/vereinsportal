@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'System', 'href' => route('system.index')], ['label' => 'Einstellungen']];
@endphp

<x-layouts.app title="Einstellungen" width="wide">
    <x-vdbs.layout.page-header title="Einstellungen" lead="Die geltenden Werte des Betriebs zum Nachlesen. Geändert werden sie in der Datei .env auf dem Server; danach unter Aufgaben „Konfiguration leeren“ ausführen." :breadcrumb="$crumbs" />

    <x-vdbs.forms.error-summary />

    @foreach ($groups as $group => $rows)
        <section aria-labelledby="settings-{{ \Illuminate\Support\Str::slug($group) }}" style="margin-top:var(--space-6)">
            <h2 id="settings-{{ \Illuminate\Support\Str::slug($group) }}" style="font-size:var(--font-size-h3)">{{ $group }}</h2>
            <x-vdbs.data.table :caption="$group" :columns="[['key' => 'label', 'label' => 'Einstellung'], ['key' => 'value', 'label' => 'Wert'], ['key' => 'key', 'label' => 'Schlüssel in der .env']]">
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['value'] }}</td>
                        <td>{{ $row['key'] ?? 'in config/portal.php' }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        </section>
    @endforeach

    <section aria-labelledby="testmail-title" style="margin-top:var(--space-8)">
        <h2 id="testmail-title" style="font-size:var(--font-size-h3)">Testmail senden</h2>
        <p>Prüft den E-Mail-Versand mit den oben genannten Einstellungen.</p>
        @if (in_array($mailer, ['log', 'array'], true))
            <x-vdbs.feedback.alert variant="warning">Der Treiber „{{ $mailer }}“ stellt nichts zu: Die Mail erscheint nur im Fehlerprotokoll.</x-vdbs.feedback.alert>
        @endif

        <x-vdbs.forms.form :action="route('system.settings.test-mail')" method="post" :summary="false">
            <x-vdbs.forms.input name="to" type="email" label="Empfänger" :required="true" maxlength="190" :value="$ownEmail" autocomplete="email" />
            <x-slot:actions>
                <x-vdbs.actions.button type="submit" icon="mail">Testmail senden</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>
    </section>
</x-layouts.app>
