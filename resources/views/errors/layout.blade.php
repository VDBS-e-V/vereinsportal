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
<body>
    <a class="vdbs-skip-link" href="#error-content">Zum Inhalt</a>

    <header class="vdbs-header">
        <div class="vdbs-container vdbs-header__inner">
            <a class="vdbs-header__brand" href="{{ url('/') }}">VDBS Portal</a>
        </div>
    </header>

    <main class="vdbs-main" id="error-content">
        <div class="vdbs-container">
            @yield('content')
        </div>
    </main>

    <footer class="vdbs-footer">
        <div class="vdbs-container">
            Verband für Demokratiebildung und Bibliotheken an Schulen e.V.
            · <a href="{{ url('/') }}">Zur Startseite</a>
        </div>
    </footer>
</body>
</html>
