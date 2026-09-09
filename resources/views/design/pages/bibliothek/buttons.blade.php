@extends('design.layout')

@section('title', 'Button-Playground')

@section('content')
    @php
        $builder = app(\App\Support\ButtonExampleBuilder::class);
        $icons = app(\App\Support\VdbsIconCatalog::class)->all();

        $example = $builder->build(
            label: request('label'),
            variant: request('variant'),
            size: request('size'),
            element: request('element'),
            icon: request('icon'),
        );
    @endphp

    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Bibliothek · Playground</p>
            <h1 class="page-title__title">Button-Generator</h1>
            <p class="page-title__lead">
                Varianten werden aus bestehenden Button-Klassen
                zusammengesetzt. Der Generator erzeugt keine neue
                Komponente, sondern wiederverwendbaren Beispielcode.
            </p>
        </header>

        <div class="library-playground vdbs-library-playground">
            <form
                class="form library-playground__controls vdbs-library-playground__controls"
                method="GET"
                action="{{ route('design.bibliothek.buttons') }}"
            >
                <div class="form__field">
                    <label class="form__label" for="button-label">
                        Beschriftung
                    </label>
                    <input
                        class="form__control"
                        id="button-label"
                        type="text"
                        name="label"
                        maxlength="80"
                        value="{{ $example['label'] }}"
                    >
                </div>

                <div class="form__field">
                    <label class="form__label" for="button-variant">
                        Variante
                    </label>
                    <select
                        class="form__control"
                        id="button-variant"
                        name="variant"
                    >
                        @foreach ($builder->variants() as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected($example['variant'] === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form__field">
                    <label class="form__label" for="button-size">
                        Größe
                    </label>
                    <select
                        class="form__control"
                        id="button-size"
                        name="size"
                    >
                        @foreach ($builder->sizes() as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected($example['size'] === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form__field">
                    <label class="form__label" for="button-element">
                        HTML-Element
                    </label>
                    <select
                        class="form__control"
                        id="button-element"
                        name="element"
                    >
                        @foreach ($builder->elements() as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected($example['element'] === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form__field">
                    <label class="form__label" for="button-icon">
                        Icon
                    </label>
                    <select
                        class="form__control"
                        id="button-icon"
                        name="icon"
                    >
                        <option value="">Kein Icon</option>
                        @foreach ($icons as $icon)
                            <option
                                value="{{ $icon }}"
                                @selected($example['icon'] === $icon)
                            >
                                {{ $icon }}
                            </option>
                        @endforeach
                    </select>
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
                    @if ($example['element'] === 'link')
                        <a class="{{ $example['class'] }}" href="#">
                            @if ($example['icon'] !== null)
                                <x-vdbs.icon
                                    :name="$example['icon']"
                                    size="18"
                                />
                            @endif
                            {{ $example['label'] }}
                        </a>
                    @else
                        <button
                            class="{{ $example['class'] }}"
                            type="button"
                        >
                            @if ($example['icon'] !== null)
                                <x-vdbs.icon
                                    :name="$example['icon']"
                                    size="18"
                                />
                            @endif
                            {{ $example['label'] }}
                        </button>
                    @endif
                </x-vdbs.code-example>
            </div>
        </div>
    </div>
@endsection
