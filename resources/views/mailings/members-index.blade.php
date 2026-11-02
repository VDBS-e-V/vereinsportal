@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Rundschreiben']];
@endphp

<x-layouts.app title="Rundschreiben" width="wide">
    <x-vdbs.layout.page-header title="Rundschreiben" lead="E-Mails an alle Mitglieder, zum Beispiel die Einladung zur Mitgliederversammlung. Sie schreiben frei und können Platzhalter für Namen und Angaben zur Versammlung nutzen." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('mailings.members.create')" icon="mail">Neues Schreiben</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="3">
        <x-vdbs.data.stat label="Empfänger zurzeit" :value="$count" icon="mail" hint="Laufende Mitgliedschaften mit E-Mail-Adresse" />
    </x-vdbs.layout.grid>

    <section aria-labelledby="sent-title" style="margin-top:var(--space-6)">
        <h2 id="sent-title" style="font-size:var(--font-size-h3)">Gesendete Schreiben</h2>

        @if ($mailings->isEmpty())
            <x-vdbs.feedback.empty-state title="Noch nichts gesendet" icon="mail"><p>Gesendete Schreiben stehen hier mit Betreff und Zahl der Empfänger. Die Empfängerliste wird nicht gespeichert.</p></x-vdbs.feedback.empty-state>
        @else
            <x-vdbs.data.table caption="Gesendete Schreiben" :columns="[['key' => 'date', 'label' => 'Gesendet'], ['key' => 'subject', 'label' => 'Betreff'], ['key' => 'count', 'label' => 'Empfänger'], ['key' => 'by', 'label' => 'Von']]">
                @foreach ($mailings as $mailing)
                    <tr>
                        <td>{{ $mailing->created_at->format('d.m.Y, H:i') }}</td>
                        <td>{{ $mailing->subject }}</td>
                        <td>{{ $mailing->recipients_count }}@if ($mailing->failed_count > 0) <x-vdbs.data.badge tone="warning">{{ $mailing->failed_count }} fehlgeschlagen</x-vdbs.data.badge>@endif</td>
                        <td>{{ $mailing->sentBy?->person?->fullName() ?? $mailing->sentBy?->email ?? '–' }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>

            <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$mailings" /></div>
        @endif
    </section>
</x-layouts.app>
