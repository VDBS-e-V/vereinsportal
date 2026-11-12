@props(['columns' => 1, 'layout' => 'stacked'])

{{--
    Detailliste (Paket "data"): Beschriftung und Wert für Profile, Details und Zusammenfassungen. Eine <dl>.
    columns  1 | 2 | 3
    layout   stacked (Beschriftung über dem Wert) | inline (Beschriftung links, Wert rechts, mit Trennlinien)
    Kinder: <x-vdbs.data.detail-item label="...">Wert</x-vdbs.data.detail-item>
--}}

@php
    $columns = in_array((int) $columns, [1, 2, 3], true) ? (int) $columns : 1;
    $layout = $layout === 'inline' ? 'inline' : 'stacked';
@endphp

<dl {{ $attributes->class(['vdbs-detail-list', "vdbs-detail-list--{$columns}", "vdbs-detail-list--{$layout}"]) }}>{{ $slot }}</dl>
