{{-- Dokumente des Onboardings: je Art die geltende Datei mit Download, Fassungen und Upload. Variablen: $kinds, $documents (Art => Fassungen), $canManage, $uploadRoute --}}
@foreach ($kinds as $kind)
    @php
        $versions = $documents[$kind->value];
        $current = $versions->first();
    @endphp
    <section aria-labelledby="doc-{{ $kind->value }}-heading" style="margin-bottom:var(--space-8)">
        <h2 id="doc-{{ $kind->value }}-heading" style="font-size:var(--font-size-h3)">{{ $kind->label() }}</h2>

        @if ($current)
            <p>Gilt: <a href="{{ route('onboarding.documents.download', $current) }}">{{ $current->original_name }}</a> (hochgeladen am {{ $current->uploaded_at->format('d.m.Y') }}, {{ number_format($current->size / 1024, 0, ',', '.') }} KB)</p>
        @else
            <p>Noch kein Dokument hochgeladen.</p>
        @endif

        @if ($versions->count() > 1)
            <details>
                <summary>Frühere Fassungen ({{ $versions->count() - 1 }})</summary>
                <ul>
                    @foreach ($versions->skip(1) as $version)
                        <li><a href="{{ route('onboarding.documents.download', $version) }}">{{ $version->original_name }}</a>, {{ $version->uploaded_at->format('d.m.Y') }}</li>
                    @endforeach
                </ul>
            </details>
        @endif

        @if ($canManage)
            <x-vdbs.forms.form :action="$uploadRoute" :files="true" :summary="false" style="margin-top:var(--space-4)">
                <input type="hidden" name="kind" value="{{ $kind->value }}">
                <x-vdbs.forms.file :id="'document-'.$kind->value" name="document" :label="$current ? 'Neue Fassung hochladen' : 'PDF hochladen'" accept=".pdf" :required="true" hint="PDF, höchstens 10 MB" />
                <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="upload">Hochladen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @endif
    </section>
@endforeach
