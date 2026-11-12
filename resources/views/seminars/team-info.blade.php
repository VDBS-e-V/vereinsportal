@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Seminare', 'href' => route('seminars.index')], ['label' => $seminar->title, 'href' => route('seminars.show', $seminar)], ['label' => 'Team informieren']];
@endphp

<x-layouts.app title="Team informieren" width="wide">
    <x-vdbs.layout.page-header title="Team informieren" lead="Eine gemeinsame Nachricht an alle im Team von {{ $seminar->title }}: Termine, Ort und Team-Übersicht." :breadcrumb="$crumbs" />

    @if ($changed)
        <x-vdbs.feedback.alert variant="info">Das Team hat sich seit der letzten Information geändert (zuletzt am {{ $seminar->team_informed_at->format('d.m.Y') }}). Senden Sie die Nachricht erneut, damit alle den Stand kennen.</x-vdbs.feedback.alert>
    @endif

    <x-vdbs.forms.form :action="route('seminars.team.info.send', $seminar)" id="team-info-form">
        <x-vdbs.forms.form-section title="Empfänger">
            <x-vdbs.data.table caption="Teammitglieder" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'team-info-form'], ['key' => 'name', 'label' => 'Name'], ['key' => 'way', 'label' => 'Weg'], ['key' => 'reach', 'label' => 'Erreichbar']]">
                @foreach ($members as $application)
                    <tr>
                        <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="application_ids[]" value="{{ $application->id }}" form="team-info-form" aria-label="{{ $application->applicantName() }} auswählen" @checked($addresses[$application->id]) @disabled(! $addresses[$application->id])></span></td>
                        <td>{{ $application->applicantName() }}</td>
                        <td>{{ $application->isContact() ? 'Persönlicher Zugang' : 'Konto' }}</td>
                        <td>@if ($addresses[$application->id])<x-vdbs.data.badge tone="success">ja</x-vdbs.data.badge>@else<x-vdbs.data.badge tone="warning">nein (gesperrt, abgemeldet oder abgeschaltet)</x-vdbs.data.badge>@endif</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="team-info-form">Nichts ausgewählt.</p>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Nachricht">
            <x-vdbs.forms.textarea name="extra_text" label="Ihr Zusatztext" :optional="true" :rows="5" maxlength="2000" :counter="true" hint="Treffpunkt, Anreise, Vorbereitung. Er steht über der Team-Übersicht." />
            <h3 style="font-size:var(--font-size-h4)">Team-Übersicht in der Nachricht</h3>
            <ul>
                @foreach ($overview as $member)
                    <li>{{ $member['name'] }}@if ($member['email']) – {{ $member['email'] }}@endif @if ($member['phone']), {{ $member['phone'] }}@endif</li>
                @endforeach
            </ul>
            <p class="vdbs-hint">Kontaktdaten stehen nur bei Personen, die der Weitergabe zugestimmt haben.</p>
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="mail">Nachricht senden</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('seminars.show', $seminar)">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
