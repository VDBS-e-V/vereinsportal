<x-account.shell title="Verbundene Anwendungen" current="apps" lead="Systeme, bei denen Sie sich über das Portal angemeldet haben. Sie können jede Freigabe widerrufen.">
    @if ($apps === [])
        <x-vdbs.feedback.empty-state title="Keine verbundene Anwendung" icon="info"><p>Sie haben sich über das Portal noch bei keinem anderen System angemeldet.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Verbundene Anwendungen" :columns="[['key' => 'name', 'label' => 'Anwendung'], ['key' => 'since', 'label' => 'Seit'], ['key' => 'act', 'label' => 'Aktion']]">
            @foreach ($apps as $app)
                <tr>
                    <td>{{ $app['name'] }}@if ($app['description'])<br><span class="vdbs-hint">{{ $app['description'] }}</span>@endif</td>
                    <td>{{ $app['since']->format('d.m.Y') }}</td>
                    <td>
                        <form method="post" action="{{ route('account.apps.destroy', $app['id']) }}" onsubmit="return confirm('Freigabe für {{ addslashes($app['name']) }} widerrufen?')">@csrf @method('DELETE')
                            <x-vdbs.actions.button type="submit" variant="secondary" icon="x">Widerrufen</x-vdbs.actions.button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-account.shell>
