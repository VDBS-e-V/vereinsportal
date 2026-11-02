@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => $area], ['label' => 'Rundschreiben']];
@endphp

<x-layouts.app title="Rundschreiben" width="wide">
    <x-vdbs.layout.page-header title="Rundschreiben" lead="E-Mails an Gruppen: Mitglieder (nur Vorstand), Teamende, Schulen, Koordination, Vorstand und Geschäftsstelle. Frei formuliert oder auf Basis einer Vorlage, mit Platzhaltern." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="text" :href="route($base.'.suppressions')">Gesperrte Adressen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('mailings.templates.index')" icon="clipboard-list">Vorlagen</x-vdbs.actions.button>
            <x-vdbs.actions.button :href="route($base.'.create')" icon="mail">Neues Schreiben</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <section aria-labelledby="sent-title">
        <h2 id="sent-title" style="font-size:var(--font-size-h3)">Gesendete Schreiben</h2>

        @if ($mailings->isEmpty())
            <x-vdbs.feedback.empty-state title="Noch nichts gesendet" icon="mail"><p>Gesendete Schreiben stehen hier mit Betreff, Gruppen und Zahl der Empfänger. Die Empfängerliste wird nicht gespeichert.</p></x-vdbs.feedback.empty-state>
        @else
            <x-vdbs.data.table caption="Gesendete Schreiben" :columns="[['key' => 'date', 'label' => 'Angelegt'], ['key' => 'subject', 'label' => 'Betreff'], ['key' => 'groups', 'label' => 'Gruppen'], ['key' => 'status', 'label' => 'Stand'], ['key' => 'count', 'label' => 'Gesendet'], ['key' => 'by', 'label' => 'Von']]">
                @foreach ($mailings as $mailing)
                    <tr class="vdbs-table__row--link">
                        <td>{{ $mailing->created_at->format('d.m.Y, H:i') }}</td>
                        <td><a class="vdbs-table__row-link" href="{{ route($base.'.show', $mailing) }}">{{ $mailing->subject }}</a></td>
                        <td>{{ $mailing->groups ? \App\Modules\Mailings\Support\MailGroups::labels($mailing->groups) : 'Mitglieder' }}</td>
                        <td><x-vdbs.data.badge :tone="$mailing->statusTone()">{{ $mailing->statusLabel() }}</x-vdbs.data.badge></td>
                        <td>{{ $mailing->sent_count }} von {{ $mailing->recipients_count }}@if ($mailing->failed_count > 0) <x-vdbs.data.badge tone="warning">{{ $mailing->failed_count }} fehlgeschlagen</x-vdbs.data.badge>@endif</td>
                        <td>{{ $mailing->sentBy?->person?->fullName() ?? $mailing->sentBy?->email ?? '–' }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>

            <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$mailings" /></div>
        @endif
    </section>
</x-layouts.app>
