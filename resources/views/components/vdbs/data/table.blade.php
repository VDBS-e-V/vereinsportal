@props(['caption', 'columns', 'zebra' => true, 'sort' => null, 'direction' => 'asc'])

{{-- $columns: list of ['key' => string, 'label' => string, 'sortable' => bool, 'href' => ?string]; Zeilen kommen als Slot (<tr>).
    Klickbare Zeile: <tr class="vdbs-table__row--link"> mit <a class="vdbs-table__row-link"> in einer Zelle, der Link deckt die ganze Zeile ab. --}}
<div class="vdbs-table-wrap" tabindex="0" role="region" aria-label="{{ $caption }}">
    <table {{ $attributes->class(['vdbs-table', 'vdbs-table--zebra' => $zebra]) }}>
        <caption class="vdbs-sr-only">{{ $caption }}</caption>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    @php($active = $sort === $column['key'])
                    <th scope="col" @if ($active) aria-sort="{{ $direction === 'desc' ? 'descending' : 'ascending' }}" @endif>
                        @if (! empty($column['sortable']) && ! empty($column['href']))
                            <a class="vdbs-table__sort" href="{{ $column['href'] }}">{{ $column['label'] }}<x-vdbs.icon :name="$active ? ($direction === 'desc' ? 'arrow-down' : 'arrow-up') : 'arrow-up-down'" :size="16" /></a>
                        @else
                            {{ $column['label'] }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
