@extends('design.layout')

@section('title', $item['name'])

@section('content')
    @php
        $categories = app(
            \App\Support\WebContentLibrary::class
        )->categories();

        $library = app(
            \App\Support\WebContentLibrary::class
        );

        $statuses = $library->statuses();

        $reference = app(
            \App\Support\WebContentLibraryReference::class
        )->for($item);

        $relatedItems = $library->related($item);
    @endphp

    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">
                {{ $categories[$item['category']] ?? $item['category'] }}
                ·
                {{ $statuses[$item['status']]['label'] ?? $item['status'] }}
            </p>
            <h1 class="page-title__title">
                {{ $item['name'] }}
            </h1>
            <p class="page-title__lead">
                {{ $item['description'] }}
            </p>
        </header>

        <div class="grid grid--2col">
            <section class="stack">
                <h2>Verwenden für</h2>
                <ul>
                    @foreach ($item['usage'] ?? [] as $text)
                        <li>{{ $text }}</li>
                    @endforeach
                </ul>
            </section>

            <section class="stack">
                <h2>Nicht verwenden für</h2>
                <ul>
                    @forelse ($item['avoid'] ?? [] as $text)
                        <li>{{ $text }}</li>
                    @empty
                        <li>Keine zusätzlichen Einschränkungen dokumentiert.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <section class="stack">
            <h2>Barrierefreiheit</h2>
            <ul>
                @foreach ($item['accessibility'] ?? [] as $text)
                    <li>{{ $text }}</li>
                @endforeach
            </ul>
        </section>

        <section class="stack">
            <h2>Quelldateien</h2>

            <div class="metadata-list">
                <div>
                    <dt>Primärquelle</dt>
                    <dd><code>{{ $item['source'] }}</code></dd>
                </div>
                <div>
                    <dt>ID</dt>
                    <dd><code>{{ $item['id'] }}</code></dd>
                </div>
                <div>
                    <dt>Tags</dt>
                    <dd>{{ implode(', ', $item['tags'] ?? []) }}</dd>
                </div>
            </div>

            <ul>
                @foreach ($item['files'] ?? [] as $file)
                    <li><code>{{ $file }}</code></li>
                @endforeach
            </ul>
        </section>

        @if ($reference['code'] !== null)
            <section class="stack">
                <h2>Code-Vorlage</h2>

                <x-vdbs.code-example
                    :title="$item['name']"
                    :code="$reference['code']"
                />
            </section>
        @endif

        @if ($relatedItems->isNotEmpty())
            <section class="stack">
                <h2>Verwandte Bausteine</h2>

                <ul class="related-links vdbs-related-links">
                    @foreach ($relatedItems as $relatedItem)
                        <li>
                            <a href="{{ route('design.bibliothek.show', ['item' => $relatedItem['id']]) }}">
                                {{ $relatedItem['name'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="button-group">
            @if ($reference['design_url'] !== null)
                <a class="btn" href="{{ $reference['design_url'] }}">
                    Design-Referenz öffnen
                </a>
            @endif

            <a class="btn btn--secondary" href="{{ route('design.bibliothek') }}">
                Zur Bibliothek
            </a>

            <a class="btn btn--secondary" href="{{ route('design.bibliothek.contribute') }}">
                Eigenen Eintrag hinzufügen
            </a>
        </div>
    </div>
@endsection
