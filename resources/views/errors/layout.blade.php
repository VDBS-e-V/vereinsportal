<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('documentTitle', 'Fehler') · VDBS Portal</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="error-shell vdbs-error-shell">
    <a class="vdbs-skip-link" href="#error-content">
        Zum Inhalt
    </a>

    <header class="error-shell__header vdbs-error-shell__header">
        <div class="error-shell__header-inner vdbs-error-shell__header-inner">
            <a
                class="error-shell__brand vdbs-error-shell__brand"
                href="{{ url('/') }}"
                aria-label="VDBS Startseite"
            >
                <img
                    src="{{ asset('images/brand/vdbs-bildmarke-breit.png') }}"
                    alt=""
                    aria-hidden="true"
                >
                <span>VDBS Portal</span>
            </a>

            <span class="error-shell__context vdbs-error-shell__context">
                Sichere Orientierung bei technischen Fehlern
            </span>
        </div>
    </header>

    <main
        class="error-shell__main vdbs-error-shell__main"
        id="error-content"
    >
        <div class="error-shell__main-inner vdbs-error-shell__main-inner">
            @yield('content')
        </div>
    </main>

    <footer class="error-shell__footer vdbs-error-shell__footer">
        <div class="error-shell__footer-inner vdbs-error-shell__footer-inner">
            <span>
                Verband für Demokratiebildung und Bibliotheken an Schulen e.V.
            </span>

            <a href="{{ url('/') }}">
                Zur Startseite
            </a>
        </div>
    </footer>
</body>

</html>
