@php
    // $area: nur die Hinweise dieses Bereichs; ohne $area alle (Konto). $showEmpty: auch „nichts zu erledigen“ sagen.
    $tasks = isset($area) ? \App\Support\TaskOverview::forArea(auth()->user(), $area) : \App\Support\TaskOverview::for(auth()->user());
    $heading = 'tasks-heading-'.(isset($area) ? $area->value : 'konto');
@endphp

@if ($tasks !== [] || ($showEmpty ?? false))
    <section aria-labelledby="{{ $heading }}" style="margin-bottom:var(--space-8)">
        <h2 id="{{ $heading }}" style="font-size:var(--font-size-h3)">Ihre Aufgaben</h2>
        @if ($tasks === [])
            <p>Im Moment gibt es nichts zu erledigen.</p>
        @else
            <ul class="vdbs-task-list">
                @foreach ($tasks as $task)
                    <li class="vdbs-task-list__item">
                        <a class="vdbs-task-list__link" href="{{ $task['href'] }}">
                            @if ($task['count'] !== null)<x-vdbs.data.badge :tone="$task['tone']">{{ $task['count'] }}</x-vdbs.data.badge>@endif
                            <span>{{ $task['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endif
