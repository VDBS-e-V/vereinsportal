@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Mitglieder']];
    $state = $membership?->effectiveStatus();
@endphp

<x-layouts.app title="Mitglieder" width="medium">
    <x-vdbs.layout.page-header title="Mitgliederbereich" lead="Ihre Mitgliedschaft, die Satzung und alles, was Sie als Mitglied brauchen." :breadcrumb="$crumbs" />

    <section aria-labelledby="membership-heading">
        <h2 id="membership-heading" style="font-size:var(--font-size-h3)">Ihre Mitgliedschaft</h2>

        @if ($membership)
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Status">{{ $state->label() }}</x-vdbs.data.detail-item>
                @if ($membership->starts_on)<x-vdbs.data.detail-item label="Mitglied seit">{{ $membership->starts_on->format('d.m.Y') }}</x-vdbs.data.detail-item>@endif
                @if ($membership->ends_on)<x-vdbs.data.detail-item label="Letzter Tag der Mitgliedschaft">{{ $membership->ends_on->format('d.m.Y') }}</x-vdbs.data.detail-item>@endif
            </x-vdbs.data.detail-list>
        @else
            <p>Zu Ihrem Konto ist keine Mitgliedschaft hinterlegt.</p>
        @endif

        <p>Ihre Kontaktdaten ändern Sie in Ihrem <a href="{{ route('account.data') }}">Stammdaten</a>.</p>
    </section>

    <section aria-labelledby="documents-heading" style="margin-top:var(--space-8)">
        <h2 id="documents-heading" style="font-size:var(--font-size-h3)">Satzung und Willkommensbrief</h2>

        <ul>
            @foreach ($documents as $row)
                <li>
                    @if ($row['document'])
                        <a href="{{ route('member.documents.download', $row['document']) }}">{{ $row['kind']->label() }} (PDF, {{ number_format($row['document']->size / 1024, 0, ',', '.') }} KB)</a>
                        <small style="color:var(--text-muted)">Stand {{ $row['document']->uploaded_at?->format('d.m.Y') }}</small>
                    @else
                        {{ $row['kind']->label() }}: wird vom Vorstand noch bereitgestellt.
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    <section aria-labelledby="events-heading" style="margin-top:var(--space-8)">
        <h2 id="events-heading" style="font-size:var(--font-size-h3)">Veranstaltungen</h2>

        @forelse ($events as $event)
            <p><a href="{{ route('events.public.show', $event->slug) }}">{{ $event->title }}</a>@if ($event->sessions->first()), {{ $event->sessions->first()->starts_at->format('d.m.Y') }}@endif</p>
        @empty
            <p>Zurzeit sind keine Veranstaltungen mit offener Anmeldung geplant.</p>
        @endforelse
    </section>

    <section aria-labelledby="contact-heading" style="margin-top:var(--space-8)">
        <h2 id="contact-heading" style="font-size:var(--font-size-h3)">Fragen und Austritt</h2>
        <p>Bei Fragen zur Mitgliedschaft, zu Ihren Daten oder zu einem Austritt wenden Sie sich bitte an den Vorstand. Einen Austritt teilen Sie dem Vorstand mit, wie es die Satzung vorsieht.</p>
        <p><x-vdbs.actions.button :href="route('portal.contact')" icon="mail">Vorstand kontaktieren</x-vdbs.actions.button></p>
    </section>
</x-layouts.app>
