@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => $area->label()]];
    $lead = match ($area) {
        \App\Support\PortalAreas::Teamende => 'Seminare, Ihr Profil und Ihre Einarbeitung.',
        \App\Support\PortalAreas::Koordination => 'Schulen, Teamende, Seminare und Veranstaltungen.',
        \App\Support\PortalAreas::Geschaeftsstelle => 'Nutzer, Anträge, Artikel, Seiten und Personen.',
        default => 'Die Seiten dieses Bereichs.',
    };
@endphp

<x-layouts.app :title="$area->label()" width="wide">
    <x-vdbs.layout.page-header :title="'Bereich '.$area->label()" :lead="$lead" :breadcrumb="$crumbs" />

    @if ($tasks !== [])
        <section aria-labelledby="tasks-heading">
            <h2 id="tasks-heading" style="font-size:var(--font-size-h3)">Was ansteht</h2>
            <ul>
                @foreach ($tasks as $task)
                    <li><a href="{{ $task['href'] }}">{{ $task['count'] !== null ? $task['count'].' ' : '' }}{{ $task['label'] }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    <section aria-labelledby="pages-heading" @if ($tasks !== []) style="margin-top:var(--space-8)" @endif>
        <h2 id="pages-heading" style="font-size:var(--font-size-h3)">Seiten</h2>

        @if ($pages === [])
            <p>In diesem Bereich gibt es für Sie zurzeit keine weiteren Seiten.</p>
        @else
            <ul class="vdbs-area-pages">
                @foreach ($pages as $page)
                    <li>
                        @if ($page['group'])<strong>{{ $page['label'] }}</strong>@else<a href="{{ $page['href'] }}"><strong>{{ $page['label'] }}</strong></a>@endif
                        @if ($page['description'])<br><span style="color:var(--text-muted)">{{ $page['description'] }}</span>@endif
                        @if ($page['children'] !== [])
                            <ul class="vdbs-area-pages__children">
                                @foreach ($page['children'] as $child)
                                    <li>
                                        <a href="{{ $child['href'] }}">{{ $child['label'] }}</a>
                                        @if ($child['description'])<span style="color:var(--text-muted)"> – {{ $child['description'] }}</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app>
