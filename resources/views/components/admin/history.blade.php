@props(['entries', 'href'])

{{--
    Verlauf eines Datensatzes aus dem Protokoll (neueste zuerst) mit Link zum gefilterten Protokoll.
    entries: Liste von AuditEntry; href: Adresse des Protokolls mit gesetztem Filter "subject"
--}}

<section aria-labelledby="history-heading" style="margin-top:var(--space-8)">
    <h2 id="history-heading" style="font-size:var(--font-size-h3)">Verlauf</h2>

    @if ($entries->isEmpty())
        <p>Noch keine Einträge im Protokoll.</p>
    @else
        <x-vdbs.data.timeline label="Verlauf aus dem Protokoll">
            @foreach ($entries as $entry)
                @php $event = $entry->eventType; @endphp
                <x-vdbs.data.timeline-item :title="$event?->label() ?? $entry->event" :time="$entry->occurred_at->format('d.m.Y, H:i')" :datetime="$entry->occurred_at->toIso8601String()" :actor="$entry->actor_label" :tone="$event?->tone() ?? 'neutral'" icon="clock" />
            @endforeach
        </x-vdbs.data.timeline>
    @endif

    <p><x-vdbs.actions.button variant="tertiary" :href="$href" icon="arrow-right">Im Protokoll öffnen</x-vdbs.actions.button></p>
</section>
