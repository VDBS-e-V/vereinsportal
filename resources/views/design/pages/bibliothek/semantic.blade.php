@extends('design.layout')

@section('title', 'Status-Playground')

@section('content')
    @php
        $builder = app(
            \App\Support\SemanticExampleBuilder::class
        );

        $example = $builder->build(
            kind: request('kind'),
            type: request('type'),
            label: request('label'),
        );
    @endphp

    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Bibliothek · Playground</p>
            <h1 class="page-title__title">Status- und Hinweis-Generator</h1>
            <p class="page-title__lead">
                Hinweise, Status und Badges haben unterschiedliche Aufgaben.
                Der Playground zeigt die vorhandenen Varianten und erzeugt
                den dazugehörigen Blade-Code.
            </p>
        </header>

        <div class="library-playground vdbs-library-playground">
            <form
                class="form library-playground__controls vdbs-library-playground__controls"
                method="GET"
                action="{{ route('design.bibliothek.semantic') }}"
            >
                <div class="form__field">
                    <label class="form__label" for="semantic-kind">
                        Baustein
                    </label>
                    <select
                        class="form__control"
                        id="semantic-kind"
                        name="kind"
                    >
                        @foreach ($builder->kinds() as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected($example['kind'] === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form__field">
                    <label class="form__label" for="semantic-type">
                        Variante
                    </label>
                    <select
                        class="form__control"
                        id="semantic-type"
                        name="type"
                    >
                        @foreach ($builder->types($example['kind']) as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected($example['type'] === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form__field">
                    <label class="form__label" for="semantic-label">
                        Text
                    </label>
                    <input
                        class="form__control"
                        id="semantic-label"
                        type="text"
                        name="label"
                        maxlength="120"
                        value="{{ $example['label'] }}"
                    >
                </div>

                <button class="btn" type="submit">
                    Vorschau aktualisieren
                </button>
            </form>

            <div class="library-playground__result vdbs-library-playground__result">
                <x-vdbs.code-example
                    title="Generierte Variante"
                    :code="$example['code']"
                >
                    @if ($example['kind'] === 'status')
                        <x-vdbs.status :type="$example['type']">
                            {{ $example['label'] }}
                        </x-vdbs.status>
                    @elseif ($example['kind'] === 'badge')
                        <x-vdbs.badge :tone="$example['type']">
                            {{ $example['label'] }}
                        </x-vdbs.badge>
                    @else
                        <x-vdbs.notice
                            :type="$example['type']"
                            :role="in_array($example['type'], ['warning', 'danger'], true) ? 'alert' : 'status'"
                        >
                            {{ $example['label'] }}
                        </x-vdbs.notice>
                    @endif
                </x-vdbs.code-example>
            </div>
        </div>
    </div>
@endsection
