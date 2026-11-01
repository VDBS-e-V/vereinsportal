@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'System', 'href' => route('system.index')], ['label' => 'Aufgaben']];
    $riskLabels = ['low' => 'Unkritisch', 'medium' => 'Mit Bestätigung', 'high' => 'Mit Passwort'];
    $riskTones = ['low' => 'success', 'medium' => 'warning', 'high' => 'error'];
    $statusLabels = ['success' => 'Erfolgreich', 'failed' => 'Fehlgeschlagen', 'running' => 'Läuft'];
    $statusTones = ['success' => 'success', 'failed' => 'error', 'running' => 'warning'];
@endphp

<x-layouts.app title="Aufgaben" width="wide">
    <x-vdbs.layout.page-header title="Aufgaben" lead="Feste Liste von Wartungsaufgaben. Jede Ausführung wird mit Ihrem Namen protokolliert. Ein freies Befehlsfeld gibt es bewusst nicht." :breadcrumb="$crumbs" />

    <x-vdbs.forms.error-summary />

    @foreach ($groups as $group => $tasks)
        <section aria-labelledby="group-{{ \Illuminate\Support\Str::slug($group) }}" style="margin-top:var(--space-6)">
            <h2 id="group-{{ \Illuminate\Support\Str::slug($group) }}" style="font-size:var(--font-size-h3)">{{ $group }}</h2>
            <x-vdbs.data.table :caption="$group" :columns="[['key' => 'task', 'label' => 'Aufgabe'], ['key' => 'risk', 'label' => 'Schutz'], ['key' => 'last', 'label' => 'Letzter Lauf'], ['key' => 'action', 'label' => 'Aktion']]">
                @foreach ($tasks as $key => $task)
                    @php $run = $last[$key] ?? null; @endphp
                    <tr>
                        <td><strong>{{ $task['label'] }}</strong><br><small>{{ $task['description'] }}</small></td>
                        <td><x-vdbs.data.badge :tone="$riskTones[$task['risk']]">{{ $riskLabels[$task['risk']] }}</x-vdbs.data.badge></td>
                        <td>
                            @if ($run)
                                <x-vdbs.data.badge :tone="$statusTones[$run->status] ?? 'neutral'">{{ $statusLabels[$run->status] ?? $run->status }}</x-vdbs.data.badge>
                                <br><small>{{ $run->started_at->format('d.m.Y, H:i') }}</small>
                            @else
                                –
                            @endif
                        </td>
                        <td>
                            @if ($task['risk'] === 'low')
                                <form method="post" action="{{ route('system.tasks.run', $key) }}">
                                    @csrf
                                    <x-vdbs.actions.button type="submit" variant="secondary" size="s">Ausführen</x-vdbs.actions.button>
                                </form>
                            @else
                                <x-vdbs.actions.button variant="secondary" size="s" data-vdbs-dialog-open="task-{{ $key }}">Ausführen …</x-vdbs.actions.button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        </section>
    @endforeach

    @foreach ($groups as $tasks)
        @foreach ($tasks as $key => $task)
            @if ($task['risk'] !== 'low')
                <x-vdbs.overlays.confirm-dialog :id="'task-'.$key" :title="$task['label']" :action="route('system.tasks.run', $key)" :variant="$task['risk'] === 'high' ? 'danger' : 'default'" confirm-label="Jetzt ausführen">
                    <p>{{ $task['description'] }}</p>
                    @if ($task['risk'] === 'high')
                        <x-slot:fields>
                            <x-vdbs.forms.password :id="'password-'.$key" name="password" label="Zur Bestätigung Ihr Passwort" :required="true" />
                        </x-slot:fields>
                    @endif
                </x-vdbs.overlays.confirm-dialog>
            @endif
        @endforeach
    @endforeach

    <section aria-labelledby="runs-title" style="margin-top:var(--space-8)">
        <h2 id="runs-title" style="font-size:var(--font-size-h3)">Letzte Läufe</h2>

        @if ($runs->isEmpty())
            <x-vdbs.feedback.empty-state title="Noch keine Läufe" icon="clipboard-list"><p>Sobald Sie eine Aufgabe ausführen, erscheint sie hier mit ihrer Ausgabe.</p></x-vdbs.feedback.empty-state>
        @else
            <x-vdbs.data.table caption="Letzte Läufe" :columns="[['key' => 'time', 'label' => 'Zeit'], ['key' => 'task', 'label' => 'Aufgabe'], ['key' => 'user', 'label' => 'Von'], ['key' => 'status', 'label' => 'Ergebnis'], ['key' => 'output', 'label' => 'Ausgabe']]">
                @foreach ($runs as $run)
                    <tr>
                        <td>{{ $run->started_at->format('d.m.Y, H:i') }}</td>
                        <td>{{ $labels[$run->task] ?? $run->task }}</td>
                        <td>{{ $run->user?->person?->fullName() ?? $run->user?->email ?? '–' }}</td>
                        <td><x-vdbs.data.badge :tone="$statusTones[$run->status] ?? 'neutral'">{{ $statusLabels[$run->status] ?? $run->status }}</x-vdbs.data.badge></td>
                        <td>
                            @if (filled($run->output))
                                <details><summary>Anzeigen</summary><pre style="white-space:pre-wrap;margin:var(--space-2) 0 0">{{ $run->output }}</pre></details>
                            @else
                                –
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif
    </section>
</x-layouts.app>
