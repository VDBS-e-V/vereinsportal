@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Sitzungen', 'href' => route('board.meetings.index')], ['label' => $meeting->title]];
    $names = fn ($users) => $users->map(fn ($user) => \App\Modules\Board\Models\BoardMeeting::attendeeName($user))->sort()->implode(', ');
@endphp

<x-layouts.app :title="$meeting->title" width="wide">
    <x-vdbs.layout.page-header :title="$meeting->title" :lead="$meeting->period().' · '.$meeting->statusLabel()" :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('board.meetings.edit', $meeting)" icon="pencil">Bearbeiten</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" href="#" icon="printer" onclick="window.print();return false;">Drucken</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.data.detail-list>
        <x-vdbs.data.detail-item label="Ort">{{ $meeting->location ?: '–' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Videokonferenz">@if ($meeting->online_link)<a href="{{ $meeting->online_link }}" target="_blank" rel="noopener noreferrer">{{ $meeting->online_link }}</a>@else – @endif</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Anwesend">{{ $present->isNotEmpty() ? $names($present) : '–' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Entschuldigt">{{ $excused->isNotEmpty() ? $names($excused) : '–' }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>

    <section style="margin-top:var(--space-6)">
        <h2 style="font-size:var(--font-size-h3)">Tagesordnung</h2>
        @if (\App\Support\Html\RichText::isEmpty($meeting->agenda))
            <p>Noch keine Tagesordnung.</p>
        @else
            <div class="vdbs-prose">{!! \App\Support\Html\RichText::clean($meeting->agenda) !!}</div>
        @endif
    </section>

    <section style="margin-top:var(--space-6)">
        <h2 style="font-size:var(--font-size-h3)">Protokoll @if ($meeting->minutes_approved_on)<x-vdbs.data.badge tone="success">genehmigt am {{ $meeting->minutes_approved_on->format('d.m.Y') }}</x-vdbs.data.badge>@elseif ($meeting->hasMinutes())<x-vdbs.data.badge tone="warning">Entwurf</x-vdbs.data.badge>@endif</h2>
        @if ($meeting->hasMinutes())
            <div class="vdbs-prose">{!! \App\Support\Html\RichText::clean($meeting->minutes) !!}</div>
            @unless ($meeting->minutes_approved_on)
                <form method="post" action="{{ route('board.meetings.approve', $meeting) }}">
                    @csrf
                    <x-vdbs.actions.button type="submit" variant="secondary" icon="check">Als genehmigt vermerken</x-vdbs.actions.button>
                </form>
            @endunless
        @else
            <p>Noch kein Protokoll. <a href="{{ route('board.meetings.edit', $meeting) }}">Protokoll schreiben</a></p>
        @endif
    </section>

    <form method="post" action="{{ route('board.meetings.destroy', $meeting) }}" style="margin-top:var(--space-8)">
        @csrf @method('delete')
        <x-vdbs.actions.button type="submit" variant="danger-text" size="s" onclick="return confirm('Diese Sitzung samt Protokoll löschen?')">Sitzung löschen</x-vdbs.actions.button>
    </form>
</x-layouts.app>
