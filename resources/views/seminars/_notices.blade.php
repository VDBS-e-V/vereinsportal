@php
    $status = $seminar->status;
    $afterTeam = in_array($status, [\App\Modules\Seminars\Enums\SeminarStatus::TeamConfirmed, \App\Modules\Seminars\Enums\SeminarStatus::Prepared], true);
@endphp

@canany(['seminars.review', 'seminars.manage'])
<section aria-labelledby="notices-heading" style="margin-top:var(--space-8)">
    <h2 id="notices-heading" style="font-size:var(--font-size-h3)">Mitteilungen</h2>
    <p class="vdbs-hint">Jede gesendete Mail steht im Verlauf (ohne Adressen).@if ($seminar->briefing_sent_at) Briefing an die Schule gesendet am {{ $seminar->briefing_sent_at->format('d.m.Y') }}.@endif @if ($seminar->onboarding_at) Onboarding: {{ $seminar->onboarding_at->format('d.m.Y, H:i') }} Uhr.@endif</p>

    @can('seminars.review')
        <x-vdbs.forms.form :action="route('seminars.notice.question', $seminar)" method="post" :summary="false">
            <x-vdbs.forms.textarea id="notice-question" name="text" label="Rückfrage an die Schule" :rows="3" maxlength="1500" hint="Die Schule bekommt den Text per E-Mail." />
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="mail">Rückfrage senden</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endcan

    @can('seminars.manage')
        <x-vdbs.forms.form :action="route('seminars.notice.briefing', $seminar)" method="post" :summary="false">
            <x-vdbs.forms.textarea id="notice-briefing" name="text" label="Briefing an die Schule" :optional="true" :rows="3" maxlength="2000" hint="Zu den Terminen aus dem Seminar: Hinweise zu Raum, Technik und Ablauf." />
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="mail">Briefing senden</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>

        @if ($afterTeam)
            <x-vdbs.forms.form :action="route('seminars.notice.onboarding', $seminar)" method="post" :summary="false">
                <x-vdbs.forms.form-grid :columns="2">
                    <x-vdbs.forms.input id="onb-at" name="onboarding_at" type="datetime-local" label="Onboarding-Videokonferenz" :required="true" :value="$seminar->onboarding_at?->format('Y-m-d\TH:i')" />
                    <x-vdbs.forms.input id="onb-link" name="onboarding_link" label="Link zur Videokonferenz" :optional="true" maxlength="255" placeholder="https://…" :value="$seminar->onboarding_link" />
                </x-vdbs.forms.form-grid>
                <x-vdbs.forms.textarea id="onb-text" name="text" label="Hinweis an das Team" :optional="true" :rows="2" maxlength="1500" />
                <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="video">Termin speichern und Team einladen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>

            <form method="post" action="{{ route('seminars.notice.rescheduled', $seminar) }}" onsubmit="return confirm('Team und Schule über die aktuellen Termine informieren?')">@csrf
                <x-vdbs.actions.button type="submit" variant="secondary" icon="calendar">Neue Termine an Team und Schule melden</x-vdbs.actions.button>
            </form>
        @endif
    @endcan
</section>
@endcanany
