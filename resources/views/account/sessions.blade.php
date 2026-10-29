<x-account.shell title="Sitzungen" current="sessions" lead="Hier sehen Sie, wo Ihr Konto angemeldet ist.">
    @php
        $columns = [['key' => 'device', 'label' => 'Gerät'], ['key' => 'ip', 'label' => 'IP-Adresse'], ['key' => 'last', 'label' => 'Zuletzt aktiv']];
    @endphp

    <x-vdbs.data.table caption="Angemeldete Sitzungen" :columns="$columns">
        @foreach ($sessions as $session)
            <tr>
                <td>
                    {{ $session['device'] }}
                    @if ($session['current']) <x-vdbs.data.badge tone="success">Diese Sitzung</x-vdbs.data.badge> @endif
                </td>
                <td>{{ $session['ip'] ?: '–' }}</td>
                <td><time datetime="{{ $session['last_activity']->toIso8601String() }}">{{ $session['last_activity']->format('d.m.Y, H:i') }} Uhr</time></td>
            </tr>
        @endforeach
    </x-vdbs.data.table>

    @unless ($listAll)
        <p style="color:var(--text-muted);font-size:var(--font-size-small)">In dieser Umgebung wird nur die aktuelle Sitzung aufgelistet. „Alle anderen abmelden“ beendet trotzdem alle weiteren Sitzungen.</p>
    @endunless

    <p style="margin-top:var(--space-5)">
        <x-vdbs.actions.button variant="danger" shape="outline" icon="log-out" data-vdbs-dialog-open="logout-others">Alle anderen Sitzungen abmelden</x-vdbs.actions.button>
    </p>

    <x-vdbs.overlays.confirm-dialog id="logout-others" title="Alle anderen Sitzungen abmelden?" variant="danger" :action="route('account.sessions.destroy')" method="delete" confirm-label="Abmelden">
        <p>Alle Geräte außer diesem müssen sich danach neu anmelden. Bestätigen Sie mit Ihrem Passwort.</p>
        <x-slot:fields><x-vdbs.forms.password id="logout-others-password" name="password" label="Passwort" :required="true" /></x-slot:fields>
    </x-vdbs.overlays.confirm-dialog>
</x-account.shell>
