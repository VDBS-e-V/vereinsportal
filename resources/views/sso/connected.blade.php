@php use App\Modules\Sso\Services\OidcClaims; @endphp
<x-account.shell title="Verbundene Anwendungen" current="apps" lead="Systeme, an die das Portal Ihre Daten nach Ihrer Zustimmung weitergibt. Sie können jede Zustimmung jederzeit widerrufen.">
    @if ($apps->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine verbundene Anwendung" icon="info"><p>Sie haben sich über das Portal noch bei keinem anderen System angemeldet.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Verbundene Anwendungen" :columns="[['key' => 'name', 'label' => 'Anwendung'], ['key' => 'data', 'label' => 'Weitergegebene Daten'], ['key' => 'since', 'label' => 'Zugestimmt am'], ['key' => 'act', 'label' => 'Aktion']]">
            @foreach ($apps as $app)
                <tr>
                    <td>{{ $app['name'] }}@if ($app['description'])<br><span class="vdbs-hint">{{ $app['description'] }}</span>@endif</td>
                    <td>{{ collect($app['scopes'])->map(fn ($id) => OidcClaims::SCOPES[$id] ?? $id)->implode('; ') }}</td>
                    <td>{{ $app['granted_at']->format('d.m.Y') }}</td>
                    <td>
                        <form method="post" action="{{ route('account.apps.destroy', $app['client_id']) }}" onsubmit="return confirm('Zustimmung für {{ addslashes($app['name']) }} widerrufen?')">@csrf @method('DELETE')
                            <x-vdbs.actions.button type="submit" variant="secondary" icon="x">Widerrufen</x-vdbs.actions.button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-account.shell>
