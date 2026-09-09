@extends('design.layout')

@section('title', 'Icon-Browser')

@section('content')
    @php
        $catalog = app(\App\Support\VdbsIconCatalog::class);
        $query = request('q');
        $icons = $catalog->search($query);
    @endphp

    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Bibliothek · Elemente</p>
            <h1 class="page-title__title">Icon-Browser</h1>
            <p class="page-title__lead">
                Die Liste wird direkt aus den vorhandenen
                <code>@case</code>-Definitionen der zentralen
                VDBS-Icon-Komponente gelesen.
            </p>
        </header>

        <form class="form" method="GET" action="{{ route('design.bibliothek.icons') }}">
            <div class="form__grid form__grid--2">
                <div class="form__field">
                    <label class="form__label" for="icon-query">
                        Icon suchen
                    </label>
                    <input
                        class="form__control"
                        id="icon-query"
                        type="search"
                        name="q"
                        value="{{ $query }}"
                        placeholder="z. B. calendar, user, copy"
                    >
                </div>

                <div class="form__field">
                    <span class="form__label" aria-hidden="true">
                        Suche
                    </span>
                    <div class="button-group">
                        <button class="btn" type="submit">
                            Suchen
                        </button>
                        <a class="btn btn--secondary" href="{{ route('design.bibliothek.icons') }}">
                            Alle anzeigen
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <p>
            <strong>{{ $icons->count() }}</strong>
            {{ $icons->count() === 1 ? 'Icon' : 'Icons' }}
        </p>

        @if ($icons->isEmpty())
            <x-vdbs.empty-state
                title="Kein Icon gefunden"
                description="Versuchen Sie einen anderen Suchbegriff."
                compact
            />
        @else
            <div class="icon-browser vdbs-icon-browser">
                @foreach ($icons as $icon)
                    <article class="icon-browser__item vdbs-icon-browser__item">
                        <div class="icon-browser__preview vdbs-icon-browser__preview">
                            <x-vdbs.icon :name="$icon" size="32" />
                        </div>

                        <p class="icon-browser__name vdbs-icon-browser__name">
                            {{ $icon }}
                        </p>

                        <x-vdbs.code-example
                            :title="'Code · '.$icon"
                            :code="$catalog->snippet($icon)"
                            compact
                        />
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection
