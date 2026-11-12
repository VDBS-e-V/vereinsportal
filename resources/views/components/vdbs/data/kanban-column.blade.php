@props(['title', 'statusId' => null, 'count' => null, 'done' => false, 'id' => null])

{{--
    Spalte der Kanban-Tafel (Paket "Daten").
    title     Name der Spalte
    statusId  Kennung der Spalte; wird beim Ablegen einer Karte an die Verschieben-Adresse der Karte gesendet
    count     Zahl der Karten (wird beim Verschieben aktuell gehalten); ohne Angabe wird gezählt
    done      Die Spalte gilt als erledigt (Hinweis im Titel)
    Slots: Standard (Karten), footer (zum Beispiel ein Formular „Hinzufügen“)
--}}

@php
    $id ??= 'kanban-col-'.\Illuminate\Support\Str::slug((string) ($statusId ?? $title)).'-'.substr(md5($title.$statusId), 0, 6);
@endphp

<section {{ $attributes->class('vdbs-kanban-column') }} aria-labelledby="{{ $id }}" data-vdbs-kanban-column @if ($statusId !== null) data-status-id="{{ $statusId }}" @endif>
    <h2 class="vdbs-kanban-column__head" id="{{ $id }}">
        <span>{{ $title }}@if ($done) <span class="vdbs-kanban-column__done">(erledigt)</span>@endif</span>
        <span class="vdbs-kanban-column__count" data-vdbs-kanban-count>{{ $count ?? '' }}</span>
    </h2>
    <ul class="vdbs-kanban-column__list" data-vdbs-kanban-list>
        {{ $slot }}
    </ul>
    @isset($footer)
        <div class="vdbs-kanban-column__footer">{{ $footer }}</div>
    @endisset
</section>
