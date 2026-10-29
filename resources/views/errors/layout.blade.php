{{--
    Gemeinsamer Rahmen der Fehlerseiten. Nutzt Kopf- und Fußbereich des Portals im "safe"-Modus: Die Seiten lesen
    weder Sitzung noch Datenbank, damit sie auch bei einem technischen Fehler (500, 503) ausgeliefert werden.
--}}
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('documentTitle', 'Fehler') · VDBS Portal</title>
    <script>
        document.documentElement.classList.add('vdbs-js'); try { var t = localStorage.getItem('vdbs-theme'); if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t; } catch (e) {}
    </script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body id="top">
    <a class="vdbs-skip-link" href="#error-content">Zum Inhalt springen</a>

    <x-vdbs.navigation.portal-header title="Fehler" :safe="true" />

    <main class="vdbs-main" id="error-content">
        <div class="vdbs-container">
            @yield('content')
        </div>
    </main>

    <x-vdbs.navigation.portal-footer :safe="true" />
</body>
</html>
