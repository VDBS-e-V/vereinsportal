<section class="stack" aria-labelledby="membership-documents-heading">
    <div class="stack stack--xs">
        <h2 id="membership-documents-heading">Dokumentenakte</h2>
        <p>Dokumente werden privat gespeichert und nur über die geschützte Verwaltung ausgeliefert. Eine neue Version ersetzt die vorherige Datei nicht physisch.</p>
    </div>

    @if ($membership->documents->isEmpty())
        <p>Noch keine Dokumente hinterlegt.</p>
    @else
        <div class="stack">
            @foreach ($membership->documents as $document)
                <article class="record-item">
                    <div class="record-item__main stack stack--xs">
                        <div class="cluster">
                            <h3 class="record-item__title">{{ $document->document_type->label() }}</h3>
                            <x-vdbs.status :type="$document->supersededBy === null ? 'success' : 'neutral'">
                                {{ $document->supersededBy === null ? 'Aktuell' : 'Ersetzt' }}
                            </x-vdbs.status>
                        </div>
                        @if ($document->label !== null)
                            <p>{{ $document->label }}</p>
                        @endif
                        <dl class="metadata-list">
                            <div><dt>Datei</dt><dd>{{ $document->original_name }}</dd></div>
                            <div><dt>Format</dt><dd>{{ $document->mime_type }}</dd></div>
                            <div><dt>Größe</dt><dd>{{ number_format($document->size_bytes / 1024, 0, ',', '.') }} KB</dd></div>
                            <div><dt>Eingang</dt><dd>{{ $document->received_on?->format('d.m.Y') ?? 'Nicht angegeben' }}</dd></div>
                            <div><dt>Hinterlegt</dt><dd>{{ $document->created_at->format('d.m.Y H:i') }}</dd></div>
                            @if ($document->supersedes_document_id !== null)
                                <div><dt>Ersetzt Dokument</dt><dd>#{{ $document->supersedes_document_id }}</dd></div>
                            @endif
                        </dl>
                    </div>
                    <div class="record-item__actions stack stack--xs">
                        <a class="btn btn--quiet" href="{{ route('administration.memberships.documents.download', [$membership, $document]) }}">Herunterladen</a>

                        @if ($canManageDocuments && $document->supersededBy === null)
                            <details>
                                <summary>Neue Version hinterlegen</summary>
                                <form class="form stack" method="post" enctype="multipart/form-data" action="{{ route('administration.memberships.documents.replace', [$membership, $document]) }}">
                                    @csrf
                                    <div class="form__field">
                                        <label class="form__label" for="replacement-document-{{ $document->id }}">Neue Datei</label>
                                        <input class="form__control" id="replacement-document-{{ $document->id }}" type="file" name="document" accept="application/pdf,image/jpeg,image/png" required>
                                    </div>
                                    <div class="form__field">
                                        <label class="form__label" for="replacement-received-on-{{ $document->id }}">Eingangsdatum</label>
                                        <input class="form__control" id="replacement-received-on-{{ $document->id }}" type="date" name="received_on">
                                    </div>
                                    <button class="btn btn--secondary" type="submit">Neue Version speichern</button>
                                </form>
                            </details>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if ($canManageDocuments)
        <details class="stack">
            <summary>Dokument hinzufügen</summary>
            <form class="form stack" method="post" enctype="multipart/form-data" action="{{ route('administration.memberships.documents.store', $membership) }}">
                @csrf
                <div class="form__field">
                    <label class="form__label" for="membership-document-type">Dokumenttyp</label>
                    <select class="form__control" id="membership-document-type" name="document_type" required>
                        @foreach ($documentTypes as $documentType)
                            <option value="{{ $documentType->value }}" @selected(old('document_type') === $documentType->value)>{{ $documentType->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form__field">
                    <label class="form__label" for="membership-document-label">Bezeichnung (optional)</label>
                    <input class="form__control" id="membership-document-label" name="label" type="text" maxlength="150" value="{{ old('label') }}">
                </div>
                <div class="form__field">
                    <label class="form__label" for="membership-document-received-on">Eingangsdatum (optional)</label>
                    <input class="form__control" id="membership-document-received-on" name="received_on" type="date" value="{{ old('received_on') }}">
                </div>
                <div class="form__field">
                    <label class="form__label" for="membership-document-file">Datei</label>
                    <input class="form__control" id="membership-document-file" name="document" type="file" accept="application/pdf,image/jpeg,image/png" required>
                    <p class="form__hint">PDF, JPEG oder PNG; maximal 10 MB.</p>
                </div>
                <button class="btn" type="submit">Dokument hinzufügen</button>
            </form>
        </details>
    @endif
</section>
