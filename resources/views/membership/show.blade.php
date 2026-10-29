@php
    $name = $person->fullName();
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Mitgliedschaften', 'href' => route('membership.index')], ['label' => $name]];
    $requested = $status === \App\Modules\Membership\Enums\MembershipStatus::Requested;
    $running = $status === \App\Modules\Membership\Enums\MembershipStatus::Active;
    $ending = $membership->isEnding();
@endphp

<x-layouts.app :title="$name" width="wide">
    <x-vdbs.layout.page-header :title="$name" :breadcrumb="$crumbs" eyebrow="Mitgliedschaft">
        <x-slot:meta>
            <x-vdbs.data.badge :tone="$status->tone()">{{ $status->label() }}</x-vdbs.data.badge>
            @if ($ending) <x-vdbs.data.badge tone="warning">Austritt zum {{ $membership->ends_on->format('d.m.Y') }}</x-vdbs.data.badge> @endif
        </x-slot:meta>

        @if ($canManage)
            <x-slot:actions>
                @if ($requested)
                    <x-vdbs.actions.button icon="check" data-vdbs-dialog-open="admit">Aufnehmen</x-vdbs.actions.button>
                    <x-vdbs.actions.button variant="danger" shape="outline" icon="x" data-vdbs-dialog-open="reject">Ablehnen</x-vdbs.actions.button>
                @endif
                @if ($running)
                    @if ($ending)
                        <x-vdbs.actions.button variant="secondary" icon="refresh-cw" data-vdbs-dialog-open="withdraw">Austritt zurücknehmen</x-vdbs.actions.button>
                    @endif
                    <x-vdbs.actions.button variant="danger" shape="outline" icon="log-out" data-vdbs-dialog-open="terminate">{{ $ending ? 'Austrittsdatum ändern' : 'Austritt eintragen' }}</x-vdbs.actions.button>
                @endif
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    <x-vdbs.layout.grid :columns="2" align="start">
        <section aria-labelledby="data-heading">
            <h2 id="data-heading" style="font-size:var(--font-size-h3)">Mitgliedschaft</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Status">{{ $status->label() }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Antrag vom">{{ $membership->requested_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Mitglied seit">{{ $membership->starts_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Letzter Tag der Mitgliedschaft">{{ $membership->ends_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Entschieden von">{{ $membership->decidedBy ? ($membership->decidedBy->person?->fullName() ?: $membership->decidedBy->email) : null }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>

        <section aria-labelledby="person-heading">
            <h2 id="person-heading" style="font-size:var(--font-size-h3)">Person</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Name">{{ $name }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Geburtsdatum">{{ $person->birth_date?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Konto">{{ $person->user ? 'vorhanden' : 'noch keins' }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
            @if ($canViewPerson)
                <p><x-vdbs.actions.button variant="secondary" :href="route('admin.people.show', $person)" icon="user">Zu den Stammdaten</x-vdbs.actions.button></p>
            @endif
            @if ($running && ! $person->user)
                <p>Die Rolle Vereinsmitglied wird vergeben, sobald die Person ein Konto hat.</p>
            @endif
        </section>
    </x-vdbs.layout.grid>

    <section aria-labelledby="history-heading" style="margin-top:var(--space-8)">
        <h2 id="history-heading" style="font-size:var(--font-size-h3)">Verlauf</h2>

        <x-vdbs.data.timeline label="Verlauf der Mitgliedschaft">
            @foreach ($events as $event)
                <x-vdbs.data.timeline-item :title="$event->type->label()" :time="$event->occurred_at->format('d.m.Y, H:i')" :datetime="$event->occurred_at->toIso8601String()" :actor="$event->actor_label ?? 'automatisch'" :tone="$event->type->tone()" icon="clock">{{ $event->comment }}</x-vdbs.data.timeline-item>
            @endforeach
        </x-vdbs.data.timeline>

        @if ($canManage)
            <x-vdbs.forms.form :action="route('membership.note', $membership)" :summary="false" style="margin-top:var(--space-6)">
                <x-vdbs.forms.textarea name="note" label="Notiz hinzufügen" maxlength="500" :counter="true" :rows="2" :required="true" />
                <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="plus">Notiz speichern</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @endif
    </section>

    @if ($canManage && $requested)
        <x-vdbs.overlays.confirm-dialog id="admit" title="Person aufnehmen?" :action="route('membership.admit', $membership)" confirm-label="Aufnehmen">
            <p>{{ $name }} wird Mitglied. Mit einem Konto erhält die Person ab dem Beitrittsdatum die Rolle Vereinsmitglied.</p>
            <x-slot:fields>
                <x-vdbs.forms.input id="admit-starts-on" name="starts_on" label="Beitrittsdatum" type="date" :required="true" :value="now()->format('Y-m-d')" />
                <x-vdbs.forms.textarea id="admit-comment" name="comment" label="Notiz" :optional="true" maxlength="500" :rows="2" />
            </x-slot:fields>
        </x-vdbs.overlays.confirm-dialog>

        <x-vdbs.overlays.confirm-dialog id="reject" title="Antrag ablehnen?" variant="danger" :action="route('membership.reject', $membership)" confirm-label="Antrag ablehnen">
            <p>Der Antrag von {{ $name }} wird abgelehnt. Der Grund bleibt im Verlauf.</p>
            <x-slot:fields>
                <x-vdbs.forms.textarea id="reject-comment" name="comment" label="Grund" :required="true" maxlength="500" :rows="3" />
            </x-slot:fields>
        </x-vdbs.overlays.confirm-dialog>
    @endif

    @if ($canManage && $running)
        <x-vdbs.overlays.confirm-dialog id="terminate" title="Austritt eintragen?" variant="danger" :action="route('membership.terminate', $membership)" confirm-label="Austritt eintragen">
            <p>Die Mitgliedschaft von {{ $name }} endet mit dem letzten Tag, den Sie eintragen. Die Rolle Vereinsmitglied endet am selben Tag. Liegt das Datum in der Vergangenheit, ist die Mitgliedschaft sofort beendet.</p>
            <x-slot:fields>
                <x-vdbs.forms.input id="terminate-ends-on" name="ends_on" label="Letzter Tag der Mitgliedschaft" type="date" :required="true" :value="$membership->ends_on?->format('Y-m-d')" />
                <x-vdbs.forms.textarea id="terminate-comment" name="comment" label="Notiz" :optional="true" maxlength="500" :rows="2" />
            </x-slot:fields>
        </x-vdbs.overlays.confirm-dialog>

        @if ($ending)
            <x-vdbs.overlays.confirm-dialog id="withdraw" title="Austritt zurücknehmen?" :action="route('membership.withdraw', $membership)" confirm-label="Austritt zurücknehmen">
                <p>Das Austrittsdatum wird entfernt, die Mitgliedschaft läuft unbefristet weiter.</p>
                <x-slot:fields>
                    <x-vdbs.forms.textarea id="withdraw-comment" name="comment" label="Notiz" :optional="true" maxlength="500" :rows="2" />
                </x-slot:fields>
            </x-vdbs.overlays.confirm-dialog>
        @endif
    @endif
</x-layouts.app>
