@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Teamende'], ['label' => 'Seminare']];
    $money = fn (int $cents) => \App\Modules\Seminars\Support\Money::format($cents);
@endphp

<x-layouts.app title="Seminare" width="wide">
    <x-vdbs.layout.page-header title="Seminare" lead="Offene Seminare, auf die Sie sich bewerben können, und Ihre eigenen Bewerbungen." :breadcrumb="$crumbs">
        @if ($hasConfirmed)
            <x-slot:actions>
                <x-vdbs.actions.button variant="secondary" :href="route('seminar-applications.calendar')" icon="calendar">Meine Termine (Kalenderdatei)</x-vdbs.actions.button>
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    <section aria-labelledby="open-heading">
        <h2 id="open-heading" style="font-size:var(--font-size-h3)">Offene Seminare</h2>

        @if ($open->isEmpty())
            <x-vdbs.feedback.empty-state title="Zurzeit keine offenen Seminare" icon="calendar">
                <p>Sobald die Koordination Seminare mit Bedarf freigibt, erscheinen sie hier.</p>
            </x-vdbs.feedback.empty-state>
        @endif

        @foreach ($open as $row)
            @php($seminar = $row['seminar'])
            <article style="margin-bottom:var(--space-6);padding:var(--space-5);background:var(--bg-surface)">
                <h3 style="margin-top:0;font-size:var(--font-size-h4)">{{ $seminar->title }}
                    <x-vdbs.data.badge>{{ $seminar->type->label() }}</x-vdbs.data.badge>
                </h3>
                <x-vdbs.data.detail-list layout="inline">
                    <x-vdbs.data.detail-item label="Ort">{{ $seminar->partner->city ?: 'wird mitgeteilt' }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Gesucht">{{ $seminar->team_needed }} Teamende, noch {{ $seminar->team_needed - $seminar->confirmed_count }} offen</x-vdbs.data.detail-item>
                    @if ($row['honorarium'])
                        <x-vdbs.data.detail-item label="Honorar">ab {{ $money($row['honorarium']) }} je Einsatztag (Überblick, ohne Pauschalen)</x-vdbs.data.detail-item>
                    @endif
                </x-vdbs.data.detail-list>

                <ul>
                    @foreach ($seminar->sessions as $session)
                        <li>{{ $session->period() }}</li>
                    @endforeach
                </ul>

                @if ($row['conflicts']->isNotEmpty())
                    <x-vdbs.feedback.alert variant="warning">Achtung: Dieser Termin überschneidet sich mit Ihrem bestätigten Seminar „{{ $row['conflicts']->pluck('title')->implode('“, „') }}“.</x-vdbs.feedback.alert>
                @endif

                @if ($row['canApply'])
                    <x-vdbs.forms.form :action="route('seminar-applications.apply', $seminar)" :summary="false">
                        <x-vdbs.forms.input :id="'note-'.$seminar->id" name="note" label="Nachricht an die Koordination" :optional="true" maxlength="300" hint="z. B. Einschränkungen bei einzelnen Terminen" />
                        <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Bewerben</x-vdbs.actions.button></x-slot:actions>
                    </x-vdbs.forms.form>
                @else
                    <p><x-vdbs.data.badge :tone="$row['application']->status->tone()">{{ $row['application']->status->label() }}</x-vdbs.data.badge></p>
                @endif
            </article>
        @endforeach
    </section>

    <section aria-labelledby="mine-heading" style="margin-top:var(--space-8)">
        <h2 id="mine-heading" style="font-size:var(--font-size-h3)">Meine Bewerbungen und Seminare</h2>

        @if ($mine->isEmpty())
            <p>Sie haben sich noch auf kein Seminar beworben.</p>
        @else
            <x-vdbs.data.table caption="Meine Bewerbungen" :columns="[['key' => 'title', 'label' => 'Seminar'], ['key' => 'dates', 'label' => 'Termine'], ['key' => 'status', 'label' => 'Stand'], ['key' => 'action', 'label' => 'Aktion']]">
                @foreach ($mine as $application)
                    @php($seminar = $application->seminar)
                    <tr>
                        <td>{{ $seminar->title }}@if ($application->status === \App\Modules\Seminars\Enums\ApplicationStatus::Confirmed)<br><small>{{ $seminar->partner->name }}, {{ $seminar->partner->city }}</small>@endif</td>
                        <td>@foreach ($seminar->sessions as $session){{ $session->period() }}<br>@endforeach</td>
                        <td>
                            <x-vdbs.data.badge :tone="$application->status->tone()">{{ $application->status->label() }}</x-vdbs.data.badge>
                            @if ($seminar->status === \App\Modules\Seminars\Enums\SeminarStatus::Cancelled)<x-vdbs.data.badge tone="error">Seminar abgesagt</x-vdbs.data.badge>@endif
                        </td>
                        <td>
                            @if ($application->status === \App\Modules\Seminars\Enums\ApplicationStatus::Applied)
                                <form method="post" action="{{ route('seminar-applications.withdraw', $seminar) }}">@csrf @method('delete')<x-vdbs.actions.button type="submit" size="s" variant="secondary" shape="outline">Zurückziehen</x-vdbs.actions.button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif
    </section>
</x-layouts.app>
