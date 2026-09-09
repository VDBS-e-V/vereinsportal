@extends('design.layout')

@section('title', 'Web Content Bibliothek')

@section('content')
    @php
        $library = app(\App\Support\WebContentLibrary::class);
        $query = request('q');
        $category = request('category');
        $status = request('status');
        $items = $library->search(
            query: $query,
            category: $category,
            status: $status,
        );
        $categories = $library->categories();
        $statuses = $library->statuses();
        $categoryCounts = $library->categoryCounts();
        $coverage = $library->coverage();
        $libraryVersion = config(
            'web_content_library.version',
            'unversioniert',
        );
        $libraryFrozen = (bool) config(
            'web_content_library.frozen',
            false,
        );
    @endphp

    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Designsystem · Arbeitsbibliothek</p>
            <h1 class="page-title__title">Web Content Bibliothek</h1>
            <p class="page-title__lead">
                Vollständiges Inventar der bewusst gepflegten VDBS-Grundlagen,
                Elemente, Muster, Content-Bausteine, Vorlagen und Werkzeuge.
            </p>
        </header>

        @if ($libraryFrozen)
            <x-vdbs.notice type="success">
                <strong>Designsystem v{{ $libraryVersion }} · Stabilitätsmodus.</strong>
                Neue Bausteine werden nicht auf Vorrat angelegt, sondern aus
                realen Anforderungen abgeleitet und danach dokumentiert.
            </x-vdbs.notice>
        @endif

        <x-vdbs.notice type="info">
            <strong>Inventarabdeckung:</strong>
            {{ $coverage['registered'] }}/{{ $coverage['discovered'] }}
            automatisch erkannte Kern-Dateien sind registriert.
            <a href="{{ route('design.bibliothek.contribute') }}">
                So fügen Sie selbst neue Inhalte hinzu.
            </a>
        </x-vdbs.notice>

        <div class="grid grid--3col">
            @foreach ($categories as $key => $label)
                <div class="card">
                    <p class="page-title__kicker">
                        {{ $label }}
                    </p>
                    <p class="page-title__title">
                        {{ $categoryCounts[$key] ?? 0 }}
                    </p>
                </div>
            @endforeach
        </div>

        <form class="form" method="GET" action="{{ route('design.bibliothek') }}">
            <div class="form__grid form__grid--4">
                <div class="form__field">
                    <label class="form__label" for="library-query">
                        Suchen
                    </label>
                    <input
                        class="form__control"
                        id="library-query"
                        type="search"
                        name="q"
                        value="{{ $query }}"
                        placeholder="z. B. Formular, Fehler, Icon"
                    >
                </div>

                <div class="form__field">
                    <label class="form__label" for="library-category">
                        Kategorie
                    </label>
                    <select
                        class="form__control"
                        id="library-category"
                        name="category"
                    >
                        <option value="">Alle Kategorien</option>
                        @foreach ($categories as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected($category === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form__field">
                    <label class="form__label" for="library-status">
                        Status
                    </label>
                    <select
                        class="form__control"
                        id="library-status"
                        name="status"
                    >
                        <option value="">Alle Status</option>
                        @foreach ($statuses as $value => $definition)
                            <option
                                value="{{ $value }}"
                                @selected($status === $value)
                            >
                                {{ $definition['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form__field">
                    <span class="form__label" aria-hidden="true">
                        Filter
                    </span>
                    <div class="button-group">
                        <button class="btn" type="submit">
                            Anwenden
                        </button>
                        <a class="btn btn--secondary" href="{{ route('design.bibliothek') }}">
                            Zurücksetzen
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <section class="stack" aria-labelledby="library-results-heading">
            <div class="table-toolbar">
                <div class="table-toolbar__primary">
                    <h2 id="library-results-heading">
                        Bibliothekseinträge
                    </h2>
                </div>
                <div class="table-toolbar__summary">
                    {{ $items->count() }} Einträge
                </div>
            </div>

            @if ($items->isEmpty())
                <x-vdbs.empty-state
                    title="Keine Einträge gefunden"
                    description="Passen Sie Suche oder Filter an."
                    compact
                />
            @else
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Kategorie</th>
                                <th scope="col">Status</th>
                                <th scope="col">Quelle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="{{ route('design.bibliothek.show', ['item' => $item['id']]) }}">
                                                {{ $item['name'] }}
                                            </a>
                                        </strong>
                                        <br>
                                        <span class="vdbs-help-text">
                                            {{ $item['description'] }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $categories[$item['category']] ?? $item['category'] }}
                                    </td>
                                    <td>
                                        <x-vdbs.status
                                            :type="match ($item['status']) {
                                                'stable' => 'success',
                                                'beta' => 'info',
                                                'experimental' => 'warning',
                                                'deprecated' => 'danger',
                                                default => 'neutral',
                                            }"
                                        >
                                            {{ $statuses[$item['status']]['label'] ?? $item['status'] }}
                                        </x-vdbs.status>
                                    </td>
                                    <td>
                                        <code>{{ $item['source'] ?? '—' }}</code>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
