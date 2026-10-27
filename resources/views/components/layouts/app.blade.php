@props(['title' => null])

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' – ' : '' }}VDBS Vereinsportal</title>
    <script>
        try { var t = localStorage.getItem('vdbs-theme'); if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t; } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="vdbs-skip-link" href="#inhalt">Zum Inhalt springen</a>

    <header class="vdbs-header">
        <div class="vdbs-container vdbs-header__inner">
            <a class="vdbs-header__brand" href="{{ url('/') }}">VDBS Vereinsportal</a>
            {{ $navigation ?? '' }}
            <button class="vdbs-theme-toggle" type="button" data-vdbs-theme-toggle>
                <x-vdbs.icon name="sun" /> Darstellung wechseln
            </button>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="vdbs-theme-toggle" type="submit"><x-vdbs.icon name="log-out" /> Abmelden</button>
                </form>
            @endauth
        </div>
    </header>

    <main id="inhalt" class="vdbs-main">
        <div class="vdbs-container">
            {{ $slot }}
        </div>
    </main>

    <footer class="vdbs-footer">
        <div class="vdbs-container">VDBS e. V.</div>
    </footer>
</body>
</html>
