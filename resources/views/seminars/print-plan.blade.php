@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Seminare'], ['label' => $seminar->number ?: $seminar->title, 'href' => $staff ? route('seminars.show', $seminar) : null], ['label' => 'Druckplaner']];
    $colors = ['bw' => 'Schwarzweiß', 'color' => 'Farbe'];
@endphp

<x-layouts.app title="Druckplaner" width="full">
    <x-vdbs.layout.container>
        <x-vdbs.layout.page-header title="Druckplaner" :lead="'Seminar '.$seminar->number.' „'.$seminar->title.'“. Wählen Sie, welche Seiten wie oft gedruckt werden, schwarzweiß oder in Farbe, und wie viele Seiten auf ein Blatt passen.'" :breadcrumb="$crumbs" />

        <x-vdbs.forms.error-summary />
        @error('print')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror

        @if ($documents->isEmpty())
            <x-vdbs.feedback.empty-state title="Keine Dokumente" icon="file-text"><p>Zu diesem Seminar liegen noch keine Dokumente, die Sie drucken können.</p></x-vdbs.feedback.empty-state>
        @else
            <form method="post" action="{{ route('seminar-print.update', $seminar) }}">@csrf @method('put')
                <x-vdbs.data.table caption="Dokumente der Seminarmappe" :columns="[['key' => 'use', 'label' => 'Drucken'], ['key' => 'doc', 'label' => 'Dokument'], ['key' => 'count', 'label' => 'Seiten im Dokument'], ['key' => 'pages', 'label' => 'Welche Seiten'], ['key' => 'copies', 'label' => 'Exemplare'], ['key' => 'color', 'label' => 'Farbe'], ['key' => 'sheet', 'label' => 'Seiten pro Blatt'], ['key' => 'duplex', 'label' => 'Beidseitig']]">
                    @foreach ($documents as $document)
                        @php
                            $item = $items->get($document->id);
                            $id = $document->id;
                        @endphp
                        <tr>
                            <td><input type="checkbox" name="items[{{ $id }}][use]" value="1" id="use-{{ $id }}" @checked(old('items.'.$id.'.use', $item !== null)) aria-label="{{ $document->title ?: $document->original_name }} drucken"></td>
                            <td><label for="use-{{ $id }}">{{ $document->title ?: $document->original_name }}</label><br><span class="vdbs-hint">{{ $document->kindLabel() }}</span></td>
                            <td>
                                @if ($document->pages)
                                    {{ $document->pages }}
                                @else
                                    <input class="vdbs-control vdbs-control--size-s" type="number" min="1" max="5000" name="items[{{ $id }}][page_count]" value="{{ old('items.'.$id.'.page_count', $item['page_count'] ?? '') }}" aria-label="Seitenzahl von {{ $document->title ?: $document->original_name }}" placeholder="Anzahl">
                                @endif
                            </td>
                            <td><input class="vdbs-control vdbs-control--size-s" type="text" name="items[{{ $id }}][pages]" maxlength="120" value="{{ old('items.'.$id.'.pages', $item['pages'] ?? '') }}" placeholder="alle oder 1-3, 5" aria-label="Seiten von {{ $document->title ?: $document->original_name }}"></td>
                            <td><input class="vdbs-control vdbs-control--size-s" type="number" min="1" max="500" name="items[{{ $id }}][copies]" value="{{ old('items.'.$id.'.copies', $item['copies'] ?? 1) }}" aria-label="Exemplare von {{ $document->title ?: $document->original_name }}"></td>
                            <td>
                                <select class="vdbs-control vdbs-control--size-s" name="items[{{ $id }}][color]" aria-label="Farbe von {{ $document->title ?: $document->original_name }}">
                                    @foreach ($colors as $value => $label)<option value="{{ $value }}" @selected(old('items.'.$id.'.color', $item['color'] ?? 'bw') === $value)>{{ $label }}</option>@endforeach
                                </select>
                            </td>
                            <td>
                                <select class="vdbs-control vdbs-control--size-s" name="items[{{ $id }}][per_sheet]" aria-label="Seiten pro Blatt von {{ $document->title ?: $document->original_name }}">
                                    @foreach ($perSheet as $count)<option value="{{ $count }}" @selected((int) old('items.'.$id.'.per_sheet', $item['per_sheet'] ?? 1) === $count)>{{ $count }}</option>@endforeach
                                </select>
                            </td>
                            <td><input type="checkbox" name="items[{{ $id }}][duplex]" value="1" @checked(old('items.'.$id.'.duplex', $item['duplex'] ?? false)) aria-label="{{ $document->title ?: $document->original_name }} beidseitig"></td>
                        </tr>
                    @endforeach
                </x-vdbs.data.table>
                <p class="vdbs-hint">Die Seitenzahl liest das Portal bei PDF-Dateien selbst. Bei anderen Dateien tragen Sie sie von Hand ein. Leer oder „alle“ druckt alle Seiten.</p>
                <x-vdbs.actions.button type="submit" icon="check">Plan speichern und berechnen</x-vdbs.actions.button>
            </form>
        @endif

        @if ($summary !== null && $summary['rows'] !== [])
            @php $t = $summary['totals']; @endphp
            <section aria-labelledby="sum-heading" style="margin-top:var(--space-8)">
                <h2 id="sum-heading" style="font-size:var(--font-size-h3)">Ergebnis</h2>
                <x-vdbs.data.detail-list layout="inline">
                    <x-vdbs.data.detail-item label="Seiten schwarzweiß">{{ $t['pages_bw'] }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Seiten in Farbe">{{ $t['pages_color'] }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Blätter schwarzweiß">{{ $t['sheets_bw'] }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Blätter in Farbe">{{ $t['sheets_color'] }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Blätter insgesamt">{{ $t['sheets'] }}</x-vdbs.data.detail-item>
                </x-vdbs.data.detail-list>
                <p>
                    <x-vdbs.actions.button :href="route('seminar-print.list', $seminar)" icon="printer" external>Druckliste öffnen</x-vdbs.actions.button>
                    <x-vdbs.actions.button :href="route('seminar-print.list', [$seminar, 'format' => 'pdf'])" variant="secondary" icon="download">Druckliste als PDF</x-vdbs.actions.button>
                </p>
            </section>
        @endif
    </x-vdbs.layout.container>
</x-layouts.app>
