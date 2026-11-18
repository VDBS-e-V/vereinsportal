@php
    $state = $membership?->effectiveStatus();
@endphp

<x-layouts.app title="Mitglieder" width="full">
    <x-vdbs.layout.area-hero :area="\App\Support\PortalAreas::Mitglieder" title="Mitgliederbereich" :greeting="$greeting" lead="Ihre Mitgliedschaft, die Satzung und alles, was Sie als Mitglied brauchen.">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('member.membership')" icon="user">Meine Mitgliedschaft</x-vdbs.actions.button>
            <x-vdbs.actions.button :href="route('portal.contact')" variant="secondary" icon="mail">Vorstand kontaktieren</x-vdbs.actions.button>
        </x-slot:actions>
        <x-slot:aside>
            <p class="vdbs-area-hero__aside-label">Ihre Mitgliedschaft</p>
            @if ($membership)
                <p class="vdbs-area-hero__aside-value">{{ $state->label() }}</p>
                @if ($membership->starts_on)<p>Mitglied seit {{ $membership->starts_on->format('d.m.Y') }}</p>@endif
            @else
                <p>Zu Ihrem Konto ist keine Mitgliedschaft hinterlegt.</p>
            @endif
        </x-slot:aside>
    </x-vdbs.layout.area-hero>

    <x-vdbs.layout.container>
        <div class="vdbs-area-sections">
            @include('portal._tasks', ['area' => \App\Support\PortalAreas::Mitglieder])

            <section aria-labelledby="membership-heading">
                <h2 id="membership-heading" style="font-size:var(--font-size-h3)">Ihre Mitgliedschaft</h2>

                @if ($membership)
                    <p>Status: <strong>{{ $state->label() }}</strong>@if ($membership->starts_on), Mitglied seit {{ $membership->starts_on->format('d.m.Y') }}@endif. <a href="{{ route('member.membership') }}">Alle Angaben zur Mitgliedschaft</a></p>
                @else
                    <p>Zu Ihrem Konto ist keine Mitgliedschaft hinterlegt.</p>
                @endif

                <p>Ihre Kontaktdaten ändern Sie unter <a href="{{ route('account.data') }}">Konto › Stammdaten</a>.</p>
            </section>

            <section aria-labelledby="documents-heading">
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

            @include('portal._area-pages', ['area' => \App\Support\PortalAreas::Mitglieder, 'cards' => $cards])

            <section aria-labelledby="contact-heading">
                <h2 id="contact-heading" style="font-size:var(--font-size-h3)">Fragen und Austritt</h2>
                <p>Bei Fragen zur Mitgliedschaft, zu Ihren Daten oder zu einem Austritt wenden Sie sich bitte an den Vorstand. Einen Austritt teilen Sie dem Vorstand mit, wie es die Satzung vorsieht.</p>
            </section>
        </div>
    </x-vdbs.layout.container>
</x-layouts.app>
