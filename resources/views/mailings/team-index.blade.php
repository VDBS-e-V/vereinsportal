@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Mitteilungen an Teamende']];
@endphp

<x-layouts.app title="Mitteilungen an Teamende" width="wide">
    <x-vdbs.layout.page-header title="Mitteilungen an Teamende" lead="E-Mails zu anstehenden Seminaren. Die Geschäftsstelle liefert die Vorlage, Sie ergänzen einen eigenen Textabschnitt." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('mailings.team.create')" icon="mail">Neue Mitteilung</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($mailings->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch nichts gesendet" icon="mail"><p>Gesendete Mitteilungen stehen hier mit Seminar und Zahl der Empfänger. Die Empfängerliste wird nicht gespeichert.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Gesendete Mitteilungen" :columns="[['key' => 'date', 'label' => 'Gesendet'], ['key' => 'seminar', 'label' => 'Seminar'], ['key' => 'audience', 'label' => 'Empfänger'], ['key' => 'count', 'label' => 'Anzahl'], ['key' => 'by', 'label' => 'Von']]">
            @foreach ($mailings as $mailing)
                <tr>
                    <td>{{ $mailing->created_at->format('d.m.Y, H:i') }}</td>
                    <td>{{ $mailing->seminar?->title ?? '–' }}</td>
                    <td>{{ \App\Modules\Mailings\Http\Controllers\TeamMailingController::AUDIENCES[$mailing->audience] ?? $mailing->audience }}</td>
                    <td>{{ $mailing->recipients_count }}@if ($mailing->failed_count > 0) <x-vdbs.data.badge tone="warning">{{ $mailing->failed_count }} fehlgeschlagen</x-vdbs.data.badge>@endif</td>
                    <td>{{ $mailing->sentBy?->person?->fullName() ?? $mailing->sentBy?->email ?? '–' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$mailings" /></div>
    @endif
</x-layouts.app>
