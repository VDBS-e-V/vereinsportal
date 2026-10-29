@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Seminare', 'href' => route('seminars.index')], ['label' => $seminar->title]];
    $statusChoices = collect($nextStatuses)->mapWithKeys(fn ($case): array => [$case->value => $case->label()])->all();
    $deletable = in_array($seminar->status, [\App\Modules\Seminars\Enums\SeminarStatus::Request, \App\Modules\Seminars\Enums\SeminarStatus::Cancelled], true);
    $person = fn ($user) => $user ? ($user->person?->fullName() ?: $user->email) : null;
    $eventText = function ($event): string {
        $data = $event->data ?? [];

        return match ($event->type) {
            \App\Modules\Seminars\Enums\SeminarEventType::StatusChanged => (\App\Modules\Seminars\Enums\SeminarStatus::tryFrom($data['von'] ?? '')?->label() ?? '').' → '.(\App\Modules\Seminars\Enums\SeminarStatus::tryFrom($data['nach'] ?? '')?->label() ?? '').($event->comment ? ': '.$event->comment : ''),
            \App\Modules\Seminars\Enums\SeminarEventType::SessionAdded, \App\Modules\Seminars\Enums\SeminarEventType::SessionRemoved => (string) ($data['termin'] ?? ''),
            \App\Modules\Seminars\Enums\SeminarEventType::QuoteCreated => 'Fassung '.($data['fassung'] ?? '').', Summe '.($data['summe'] ?? ''),
            \App\Modules\Seminars\Enums\SeminarEventType::Updated => 'Geändert: '.implode(', ', $data['felder'] ?? []),
            default => (string) $event->comment,
        };
    };
@endphp

<x-layouts.app :title="$seminar->title" width="wide">
    <x-vdbs.layout.page-header :title="$seminar->title" :breadcrumb="$crumbs" eyebrow="{{ $seminar->type->label() }}">
        <x-slot:meta><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></x-slot:meta>

        @if ($canManage)
            <x-slot:actions>
                @if ($statusChoices !== [])
                    <x-vdbs.actions.button icon="refresh-cw" data-vdbs-dialog-open="change-status">Status ändern</x-vdbs.actions.button>
                @endif
                <x-vdbs.actions.button variant="secondary" :href="route('seminars.quotes.create', $seminar)" icon="clipboard-list">Kostenvoranschlag berechnen</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="secondary" :href="route('seminars.edit', $seminar)" icon="pencil">Bearbeiten</x-vdbs.actions.button>
                @if ($deletable)
                    <x-vdbs.actions.button variant="danger" shape="outline" icon="trash-2" data-vdbs-dialog-open="delete-seminar">Löschen</x-vdbs.actions.button>
                @endif
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    <x-vdbs.layout.grid :columns="2" align="start">
        <section aria-labelledby="seminar-heading">
            <h2 id="seminar-heading" style="font-size:var(--font-size-h3)">Seminar</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Titel">{{ $seminar->title }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Leistung">{{ $seminar->type->label() }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Schule"><a href="{{ route('partners.show', $seminar->partner) }}">{{ $seminar->partner->name }}</a></x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Klassenstufe">{{ $seminar->grade_level }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Erwartete Teilnehmende">{{ $seminar->expected_participants }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Status">{{ $seminar->status->label() }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>

            @if (filled($seminar->notes))
                <h3 style="margin-top:var(--space-6);font-size:var(--font-size-h4)">Notiz</h3>
                <p style="white-space:pre-line">{{ $seminar->notes }}</p>
            @endif

            <p style="color:var(--text-muted);font-size:var(--font-size-small)">
                Angelegt am {{ $seminar->created_at?->format('d.m.Y') }}@if ($seminar->createdBy) von {{ $person($seminar->createdBy) }}@endif.
            </p>
        </section>

        <section aria-labelledby="quotes-heading">
            <h2 id="quotes-heading" style="font-size:var(--font-size-h3)">Kostenvoranschläge</h2>
            @if ($seminar->quotes->isEmpty())
                <p>Noch kein Kostenvoranschlag.</p>
            @else
                <x-vdbs.data.table caption="Kostenvoranschläge" :columns="[['key' => 'version', 'label' => 'Fassung'], ['key' => 'date', 'label' => 'Erstellt'], ['key' => 'total', 'label' => 'Summe']]">
                    @foreach ($seminar->quotes as $quote)
                        <tr class="vdbs-table__row--link">
                            <td><a class="vdbs-table__row-link" href="{{ route('seminars.quotes.show', [$seminar, $quote]) }}">Fassung {{ $quote->version }}</a></td>
                            <td>{{ $quote->created_at?->format('d.m.Y') }}</td>
                            <td>{{ \App\Modules\Seminars\Support\Money::format($quote->total_cents) }}</td>
                        </tr>
                    @endforeach
                </x-vdbs.data.table>
            @endif
        </section>
    </x-vdbs.layout.grid>

    <section aria-labelledby="sessions-heading" style="margin-top:var(--space-8)">
        <h2 id="sessions-heading" style="font-size:var(--font-size-h3)">Termine</h2>

        @if ($seminar->sessions->isEmpty())
            <p>Noch kein Termin eingetragen.</p>
        @else
            <x-vdbs.data.table caption="Termine des Seminars" :columns="[['key' => 'period', 'label' => 'Zeitraum'], ['key' => 'location', 'label' => 'Ort'], ['key' => 'notes', 'label' => 'Hinweis'], ['key' => 'action', 'label' => 'Aktion']]">
                @foreach ($seminar->sessions as $session)
                    <tr>
                        <td>{{ $session->period() }}</td>
                        <td>{{ $session->location ?? '–' }}</td>
                        <td>{{ $session->notes ?? '–' }}</td>
                        <td>
                            @if ($canManage)
                                <x-vdbs.actions.button variant="danger" shape="outline" size="s" data-vdbs-dialog-open="remove-session-{{ $session->id }}">Entfernen</x-vdbs.actions.button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif

        @if ($canManage)
            <x-vdbs.forms.form :action="route('seminars.sessions.store', $seminar)" :summary="false" style="margin-top:var(--space-6)">
                <x-vdbs.forms.form-grid :columns="3">
                    <x-vdbs.forms.input name="date" label="Datum" type="date" :required="true" />
                    <x-vdbs.forms.input name="start" label="Beginn" type="time" :required="true" value="09:00" />
                    <x-vdbs.forms.input name="end" label="Ende" type="time" :required="true" value="15:00" />
                    <x-vdbs.forms.input name="location" label="Ort" :optional="true" :value="$seminar->partner->name" hint="Standard: die Schule" />
                    <x-vdbs.forms.input name="notes" label="Hinweis" :optional="true" />
                </x-vdbs.forms.form-grid>
                <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="plus">Termin eintragen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @endif
    </section>

    <section aria-labelledby="history-heading" style="margin-top:var(--space-8)">
        <h2 id="history-heading" style="font-size:var(--font-size-h3)">Verlauf</h2>

        <x-vdbs.data.timeline label="Verlauf des Seminars">
            @foreach ($seminar->events as $event)
                <x-vdbs.data.timeline-item :title="$event->type->label()" :time="$event->occurred_at->format('d.m.Y, H:i')" :datetime="$event->occurred_at->toIso8601String()" :actor="$event->actor_label ?? 'automatisch'" :tone="$event->type->tone()" icon="clock">{{ $eventText($event) }}</x-vdbs.data.timeline-item>
            @endforeach
        </x-vdbs.data.timeline>

        @if ($canManage)
            <x-vdbs.forms.form :action="route('seminars.note', $seminar)" :summary="false" style="margin-top:var(--space-6)">
                <x-vdbs.forms.textarea name="note" label="Notiz hinzufügen" maxlength="500" :counter="true" :rows="2" :required="true" />
                <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="plus">Notiz speichern</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @endif
    </section>

    @if ($canManage)
        @if ($statusChoices !== [])
            <x-vdbs.overlays.confirm-dialog id="change-status" title="Status ändern?" :action="route('seminars.status', $seminar)" confirm-label="Status ändern">
                <p>Der Wechsel steht im Verlauf. Für „Terminiert“ und „Bestätigt“ braucht das Seminar einen Termin, für „Bestätigt“ zusätzlich einen Kostenvoranschlag.</p>
                <x-slot:fields>
                    <x-vdbs.forms.select id="status-new" name="status" label="Neuer Status" :required="true" :options="$statusChoices" />
                    <x-vdbs.forms.textarea id="status-comment" name="comment" label="Notiz" :optional="true" maxlength="500" :rows="2" />
                </x-slot:fields>
            </x-vdbs.overlays.confirm-dialog>
        @endif

        @if ($deletable)
            <x-vdbs.overlays.confirm-dialog id="delete-seminar" title="Seminar löschen?" variant="danger" :action="route('seminars.destroy', $seminar)" method="delete" confirm-label="Seminar löschen">
                <p>Das Seminar <strong>{{ $seminar->title }}</strong> wird mit Terminen, Verlauf und Kostenvoranschlägen dauerhaft gelöscht.</p>
            </x-vdbs.overlays.confirm-dialog>
        @endif

        @foreach ($seminar->sessions as $session)
            <x-vdbs.overlays.confirm-dialog id="remove-session-{{ $session->id }}" title="Termin entfernen?" variant="danger" :action="route('seminars.sessions.destroy', [$seminar, $session])" method="delete" confirm-label="Termin entfernen">
                <p>Der Termin {{ $session->period() }} wird entfernt. Der Verlauf hält das fest.</p>
            </x-vdbs.overlays.confirm-dialog>
        @endforeach
    @endif
</x-layouts.app>
