@extends('design.layout')

@section('title', 'Layout')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Designsystem</p>
            <h1 class="page-title__title">Layout</h1>
            <p class="page-title__lead">
                Breite und horizontaler Seitenrand werden getrennt festgelegt.
                Dadurch lassen sich kleine, normale, breite und vollflächige
                Layouts mit beidseitigem, keinem oder einseitigem Rand kombinieren.
            </p>
        </header>

        <section class="section stack">
            <h2>Breiten</h2>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Option</th>
                            <th>Zielwert</th>
                            <th>Blade</th>
                            <th>CSS</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>Small</td>
                            <td>ca. 960 px</td>
                            <td><code>width="small"</code></td>
                            <td><code>layout-frame--small</code></td>
                        </tr>
                        <tr>
                            <td>Normal</td>
                            <td>ca. 1280 px</td>
                            <td><code>width="normal"</code></td>
                            <td><code>layout-frame--normal</code></td>
                        </tr>
                        <tr>
                            <td>Breit</td>
                            <td>ca. 1400 px</td>
                            <td><code>width="wide"</code></td>
                            <td><code>layout-frame--wide</code></td>
                        </tr>
                        <tr>
                            <td>Full Width</td>
                            <td>100 %</td>
                            <td><code>width="full"</code></td>
                            <td><code>layout-frame--full</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="section stack">
            <h2>Seitenränder</h2>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Option</th>
                            <th>Blade</th>
                            <th>CSS</th>
                            <th>Wirkung</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>Mit Rand</td>
                            <td><code>gutter="both"</code></td>
                            <td><code>layout-frame--gutter</code></td>
                            <td>Beidseitig dynamisch ca. 32–56 px.</td>
                        </tr>
                        <tr>
                            <td>Ohne Rand</td>
                            <td><code>gutter="none"</code></td>
                            <td><code>layout-frame--flush</code></td>
                            <td>Inhalt darf beide Kanten erreichen.</td>
                        </tr>
                        <tr>
                            <td>Nur Start</td>
                            <td><code>gutter="start"</code></td>
                            <td><code>layout-frame--gutter-start</code></td>
                            <td>In LTR links; in RTL automatisch rechts.</td>
                        </tr>
                        <tr>
                            <td>Nur Ende</td>
                            <td><code>gutter="end"</code></td>
                            <td><code>layout-frame--gutter-end</code></td>
                            <td>In LTR rechts; in RTL automatisch links.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="section stack">
            <h2>Beispiele</h2>

            @foreach ([
                ['small', 'both', 'Small · mit Rand'],
                ['normal', 'both', 'Normal · mit Rand'],
                ['wide', 'both', 'Breit · mit Rand'],
                ['full', 'both', 'Full Width · mit Rand'],
                ['full', 'none', 'Full Width · ohne Rand'],
                ['full', 'start', 'Full Width · nur Start-Rand'],
                ['full', 'end', 'Full Width · nur End-Rand'],
            ] as [$width, $gutter, $label])
                <div class="design-layout-preview">
                    <x-vdbs.frame
                        :width="$width"
                        :gutter="$gutter"
                        class="design-layout-preview__frame"
                    >
                        <span class="design-layout-preview__label">
                            {{ $label }}
                        </span>
                    </x-vdbs.frame>
                </div>
            @endforeach
        </section>

        <section class="section stack">
            <h2>Verwendung</h2>

            <pre class="design-code">&lt;x-vdbs.frame width="small" gutter="both"&gt;
    ...
&lt;/x-vdbs.frame&gt;

&lt;x-vdbs.frame width="normal" gutter="none"&gt;
    ...
&lt;/x-vdbs.frame&gt;

&lt;x-vdbs.frame width="wide" gutter="start"&gt;
    ...
&lt;/x-vdbs.frame&gt;

&lt;x-vdbs.frame width="full" gutter="both"&gt;
    ...
&lt;/x-vdbs.frame&gt;</pre>

            <div class="notice">
                <strong>Portal Header:</strong>
                Die beiden Header-Zeilen und das Mobile-Menü verwenden
                <code>Full Width + mit Rand</code>. Der Pfad verwendet
                <code>Normal + mit Rand</code>.
            </div>
        </section>
    </div>
@endsection
