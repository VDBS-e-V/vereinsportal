@extends('design.layout')

@section('title', 'Grundlagen')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Designsystem</p>
            <h1 class="page-title__title">Grundlagen</h1>
            <p class="page-title__lead">
                White und Ink bilden die ruhige Basis. VDBS Green ist die primäre
                Markenakzentfarbe; Ocean Mist und weitere Markenfarben werden gezielt
                für bestimmte Funktionen und Hervorhebungen eingesetzt.
            </p>
        </header>

        <section class="stack">
            <h2>Farben</h2>

            <div class="design-swatches">
                @foreach ([
                    ['VDBS Green', '#2FBF71', 'var(--color-primary)'],
                    ['Ocean Mist', '#22B7A3', 'var(--color-secondary-cta)'],
                    ['Ink', '#0F172A', 'var(--color-ink)'],
                    ['White', '#FFFFFF', 'var(--color-white)'],
                    ['Amber', '#F59E0B', 'var(--color-accent-amber)'],
                    ['Teal', '#14B8A6', 'var(--color-accent-teal)'],
                    ['Berry', '#D946EF', 'var(--color-accent-berry)'],
                ] as [$name, $hex, $token])
                    <article class="design-swatch">
                        <div
                            class="design-swatch__color"
                            style="background: {{ $hex }}"
                            aria-hidden="true"
                        ></div>
                        <div class="design-swatch__meta">
                            <strong>{{ $name }}</strong>
                            <code>{{ $hex }}</code>
                            <code>{{ $token }}</code>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="section stack">
            <h2>Typografie</h2>

            <div class="design-token-list">
                <div class="design-token-row">
                    <strong>IBM Plex Sans</strong>
                    <div class="stack stack--sm">
                        <h1>Seitentitel</h1>
                        <h2>Abschnitt</h2>
                        <p>Fließtext, Formulare und Navigation verwenden die Sans-Serif-Basis.</p>
                    </div>
                </div>

                <div class="design-token-row">
                    <strong>Source Serif 4</strong>
                    <p class="editorial-accent">
                        Besondere Aussagen und kurze redaktionelle Hervorhebungen dürfen
                        bewusst eine wärmere typografische Stimme erhalten.
                    </p>
                </div>
            </div>
        </section>

        <section class="section stack">
            <h2>Abstände und Seitenbreite</h2>

            <div class="table-wrapper">
                <table class="table">
                    <tbody>
                        <tr>
                            <th>Standardbreite</th>
                            <td><code>--layout-content-max</code> · 1280 px</td>
                        </tr>
                        <tr>
                            <th>Breite Variante</th>
                            <td><code>--layout-content-wide-max</code> · 1440 px</td>
                        </tr>
                        <tr>
                            <th>Seitenrand</th>
                            <td><code>--layout-page-gutter</code> · dynamisch 32–56 px</td>
                        </tr>
                        <tr>
                            <th>Spacing</th>
                            <td><code>--spacing-1</code> bis <code>--spacing-10</code>, 4-px-Grundrhythmus</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="section stack">
            <h2>Form und Tiefe</h2>
            <p>
                Normale Oberflächen bleiben eckig. Schatten sind erlaubt und werden
                insbesondere für Sticky Header, Dropdowns und klar hervorgehobene Ebenen
                eingesetzt. Avatare sind die bewusste runde Ausnahme.
            </p>

            <div class="grid grid--3col">
                <div class="panel">Normale Fläche</div>
                <div class="panel" style="box-shadow: var(--shadow-md)">Mittlere Hervorhebung</div>
                <div class="panel" style="box-shadow: var(--shadow-lg)">Starke Überlagerung</div>
            </div>
        </section>
    </div>
@endsection
