@props(['title' => null, 'width' => 'default'])

{{-- width: narrow | medium | default | wide | full (siehe <x-vdbs.layout.container>). Bei "full" bringen Abschnitte ihren eigenen Container mit. --}}

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' – ' : '' }}VDBS Vereinsportal</title>
    <script>
        document.documentElement.classList.add('vdbs-js'); try { var t = localStorage.getItem('vdbs-theme'); if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t; } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body id="top">
    <a class="vdbs-skip-link" href="#inhalt">Zum Inhalt springen</a>

    <x-vdbs.navigation.portal-header :title="$title" />

    <x-vdbs.feedback.flash />

    <main id="inhalt" @class(['vdbs-main', 'vdbs-main--full' => $width === 'full'])>
        @if ($width === 'full')
            {{ $slot }}
        @else
            <x-vdbs.layout.container :width="$width">{{ $slot }}</x-vdbs.layout.container>
        @endif
    </main>

    <x-vdbs.navigation.portal-footer />
</body>
</html>
