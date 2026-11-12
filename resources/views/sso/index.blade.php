@php($crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'Anbindungen']])
<x-layouts.app title="Anbindungen" width="wide">
    <x-vdbs.layout.page-header title="Anbindungen" lead="Systeme wie Nextcloud und Moodle, bei denen sich Nutzende über das Portal anmelden. Das Portal bleibt die einzige Anmeldung." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('admin.oauth.create')" icon="plus">Neue Anbindung</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <section aria-labelledby="keys-heading">
        <h2 id="keys-heading" style="font-size:var(--font-size-h3)">Anmeldeschlüssel</h2>
        @error('keys') <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert> @enderror
        @if ($keys['exists'])
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Zustand"><x-vdbs.data.badge tone="success">vorhanden</x-vdbs.data.badge></x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Erzeugt am">{{ $keys['created_at']->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Fingerabdruck">{{ $keys['fingerprint'] }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
            <form method="post" action="{{ route('admin.oauth.keys.renew') }}" style="margin-top:var(--space-3)" onsubmit="return confirm('Schlüssel wirklich erneuern? Alle Anmeldungen über das Portal laufen ab.')">@csrf
                <x-vdbs.feedback.alert variant="warning">Beim Erneuern laufen alle Anmeldungen bei Nextcloud, Moodle und anderen Systemen ab. Die Nutzenden melden sich danach einmal neu an. Die Schlüssel gehören in die Sicherung.</x-vdbs.feedback.alert>
                <x-vdbs.forms.password name="password" label="Ihr Passwort zur Bestätigung" :required="true" autocomplete="current-password" />
                <x-vdbs.actions.button type="submit" variant="secondary" icon="refresh-cw">Schlüssel erneuern</x-vdbs.actions.button>
            </form>
        @else
            <x-vdbs.feedback.alert variant="warning">Es gibt noch keine Schlüssel. Ohne Schlüssel funktioniert keine Anmeldung über das Portal.</x-vdbs.feedback.alert>
            <form method="post" action="{{ route('admin.oauth.keys.create') }}">@csrf
                <x-vdbs.actions.button type="submit" icon="check">Schlüssel erzeugen</x-vdbs.actions.button>
            </form>
        @endif
    </section>

    <section aria-labelledby="clients-heading" style="margin-top:var(--space-8)">
        <h2 id="clients-heading" style="font-size:var(--font-size-h3)">Angebundene Systeme</h2>
        @if ($clients->isEmpty())
            <x-vdbs.feedback.empty-state title="Noch keine Anbindung" icon="info"><p>Legen Sie für jedes System, das sich über das Portal anmelden soll, eine Anbindung an.</p></x-vdbs.feedback.empty-state>
        @else
            <x-vdbs.data.table caption="Anbindungen" :columns="[['key' => 'name', 'label' => 'Name'], ['key' => 'id', 'label' => 'Client-ID'], ['key' => 'kind', 'label' => 'Art'], ['key' => 'roles', 'label' => 'Erlaubte Rollen'], ['key' => 'state', 'label' => 'Stand']]">
                @foreach ($clients as $client)
                    @php($setting = $settings[(string) $client->getKey()])
                    <tr>
                        <td><a href="{{ route('admin.oauth.show', $client->getKey()) }}">{{ $client->name }}</a></td>
                        <td style="word-break:break-all">{{ $client->getKey() }}</td>
                        <td>{{ $client->confidential() ? 'vertraulich' : 'öffentlich' }}</td>
                        <td>{{ collect($setting->allowed_roles ?? [])->map(fn ($key) => \App\Modules\AccessGovernance\Enums\RoleKey::tryFrom($key)?->label() ?? $key)->implode(', ') ?: 'alle' }}</td>
                        <td>@if ($client->revoked)<x-vdbs.data.badge tone="error">gesperrt</x-vdbs.data.badge>@else<x-vdbs.data.badge tone="success">aktiv</x-vdbs.data.badge>@endif</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif
    </section>
</x-layouts.app>
