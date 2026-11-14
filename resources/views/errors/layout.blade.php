{{--
    Gemeinsamer Rahmen der Fehlerseiten. Bei Anwendungsfehlern (403, 404, 419 u. Ä.) zeigt er Kopf und Fuß wie sonst:
    angemeldete Personen sehen ihre Bereiche, Gäste den Login. Nur bei technischen Fehlern (ab 500, auch 503) läuft er im
    "safe"-Modus: Dann werden weder Sitzung noch Datenbank gelesen, damit die Seite sicher ausgeliefert wird.
--}}
@php
    $errorStatus = isset($exception) && method_exists($exception, 'getStatusCode') ? (int) $exception->getStatusCode() : 500;
    $safeMode = $errorStatus >= 500;
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('documentTitle', 'Fehler') · VDBS Vereinsportal</title>
    <script>
        document.documentElement.classList.add('vdbs-js'); try { var t = localStorage.getItem('vdbs-theme'); if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t; } catch (e) {}
    </script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body id="top">
    <a class="vdbs-skip-link" href="#error-content">Zum Inhalt springen</a>

    <x-vdbs.navigation.portal-header title="Fehler" :safe="$safeMode" />

    <main class="vdbs-main" id="error-content">
        <div class="vdbs-container">
            @yield('content')
        </div>
    </main>

    <x-vdbs.navigation.portal-footer :safe="$safeMode" />
</body>
</html>
