@props(['title', 'href' => null, 'moveUrl' => null, 'priority' => 'normal'])

{{--
    Karte der Kanban-Tafel (Paket "Daten"). Ein <li> in <x-vdbs.data.kanban-column>.
    title     Titel der Karte (verlinkt, wenn href gesetzt ist)
    moveUrl   Adresse, an die das Ablegen in eine andere Spalte gesendet wird (POST mit status_id und index); ohne Angabe ist die Karte nicht verschiebbar
    priority  normal | high (hohe Priorität bekommt einen Rand in der Warnfarbe)
    Slots: Standard (Angaben unter dem Titel, zum Beispiel Badges), footer (zum Beispiel das „Verschieben“-Formular)
--}}

@php
    $priority = in_array($priority, ['normal', 'high'], true) ? $priority : 'normal';
@endphp

<li {{ $attributes->class(['vdbs-kanban-card', "vdbs-kanban-card--{$priority}"]) }} data-vdbs-kanban-card @if ($moveUrl) data-move-url="{{ $moveUrl }}" draggable="true" @endif>
    @if ($href)<a class="vdbs-kanban-card__title" href="{{ $href }}">{{ $title }}</a>@else<span class="vdbs-kanban-card__title">{{ $title }}</span>@endif
    @if (! $slot->isEmpty())<div class="vdbs-kanban-card__body">{{ $slot }}</div>@endif
    @isset($footer)
        <div class="vdbs-kanban-card__footer">{{ $footer }}</div>
    @endisset
</li>
