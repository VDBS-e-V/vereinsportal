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

    <x-vdbs.portal-header :title="$title" />

    <main id="inhalt" class="vdbs-main">
        <div class="vdbs-container">
            {{ $slot }}
        </div>
    </main>

    <x-vdbs.portal-footer />
</body>
</html>
