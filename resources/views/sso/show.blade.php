@php($crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'Anbindungen', 'href' => route('admin.oauth.index')], ['label' => $client->name]])
<x-layouts.app :title="$client->name" width="medium">
    <x-vdbs.layout.page-header :title="$client->name" :lead="$setting->description" :breadcrumb="$crumbs">
        <x-slot:actions><x-vdbs.actions.button variant="secondary" :href="route('admin.oauth.edit', $client->getKey())" icon="pencil">Bearbeiten</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($secret)
        <x-vdbs.feedback.alert variant="warning" title="Geheimnis jetzt kopieren">
            <p>Das Geheimnis wird nur jetzt angezeigt. Danach ist nur noch ein Hash gespeichert.</p>
            <p><strong>Geheimnis:</strong> <code id="oauth-secret" style="word-break:break-all">{{ $secret }}</code></p>
        </x-vdbs.feedback.alert>
    @endif

    <x-vdbs.data.detail-list layout="inline">
        <x-vdbs.data.detail-item label="Client-ID"><code style="word-break:break-all">{{ $client->getKey() }}</code></x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Art">{{ $client->confidential() ? 'vertraulich' : 'öffentlich' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Stand">{{ $client->revoked ? 'gesperrt' : 'aktiv' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Umleitungsadressen">{{ implode(', ', $client->redirect_uris) }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Erlaubte Rollen">{{ collect($setting->allowed_roles ?? [])->map(fn ($key) => $roleOptions[$key] ?? $key)->implode(', ') ?: 'alle' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Zustimmungsseite">{{ $setting->show_consent ? 'ja' : 'nein' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Ansprechperson">{{ $setting->contact ?: '–' }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>

    <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Anbindung pflegen</h2>
    @if ($client->confidential())
        <form method="post" action="{{ route('admin.oauth.secret', $client->getKey()) }}" style="margin-bottom:var(--space-5)">@csrf
            <x-vdbs.forms.password name="password" label="Ihr Passwort" :required="true" autocomplete="current-password" id="pw-secret" />
            <x-vdbs.actions.button type="submit" variant="secondary" icon="refresh-cw">Geheimnis erneuern</x-vdbs.actions.button>
        </form>
    @endif
    <form method="post" action="{{ route('admin.oauth.block', $client->getKey()) }}" style="margin-bottom:var(--space-5)">@csrf
        <input type="hidden" name="action" value="{{ $client->revoked ? 'unblock' : 'block' }}">
        <x-vdbs.actions.button type="submit" variant="secondary">{{ $client->revoked ? 'Wieder freigeben' : 'Sperren (beendet alle Anmeldungen)' }}</x-vdbs.actions.button>
    </form>
    <form method="post" action="{{ route('admin.oauth.destroy', $client->getKey()) }}" onsubmit="return confirm('Anbindung endgültig löschen?')">@csrf @method('DELETE')
        <x-vdbs.forms.password name="password" label="Ihr Passwort zum Löschen" :required="true" autocomplete="current-password" id="pw-delete" />
        <x-vdbs.actions.button type="submit" variant="secondary" icon="trash-2">Anbindung löschen</x-vdbs.actions.button>
    </form>
</x-layouts.app>
