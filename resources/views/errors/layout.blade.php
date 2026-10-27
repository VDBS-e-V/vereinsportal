<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('documentTitle', 'Fehler') · VDBS Portal</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    @php
        /*
         * Normaler Portal-Header und -Footer. Ab Status 500 ohne Sitzung und
         * Datenbank; schlägt das fehl, greift die reduzierte Hülle (null).
         * Siehe App\Support\ErrorPageChrome.
         */
        $errorStatus = isset($exception)
            && $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                ? $exception->getStatusCode()
                : null;

        $errorHeader = \App\Support\ErrorPageChrome::header(
            $errorStatus,
            trim($__env->yieldContent('documentTitle')) ?: 'Fehler',
        );
        $errorFooter = \App\Support\ErrorPageChrome::footer();
    @endphp
</head>

<body class="vdbs-app-shell error-shell vdbs-error-shell">
    <a class="vdbs-skip-link" href="#error-content">
        Zum Inhalt
    </a>

    @if ($errorHeader !== null)
        {!! $errorHeader !!}
    @else
        @include('errors.partials.fallback-header')
    @endif

    <main
        class="error-shell__main vdbs-error-shell__main"
        id="error-content"
    >
        <div class="error-shell__main-inner vdbs-error-shell__main-inner">
            @yield('content')
        </div>
    </main>

    @if ($errorFooter !== null)
        {!! $errorFooter !!}
    @else
        @include('errors.partials.fallback-footer')
    @endif
</body>

</html>
