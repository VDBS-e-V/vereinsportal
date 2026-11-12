<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Einrichtung') – VDBS Vereinsportal</title>
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.5; max-width: 46rem; margin: 2rem auto; padding: 0 1rem; color: CanvasText; background: Canvas; }
        h1 { font-size: 1.6rem; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { text-align: left; vertical-align: top; padding: .35rem .5rem; border-bottom: 1px solid GrayText; }
        .ok { color: green; font-weight: 600; } .warning { color: darkorange; font-weight: 600; } .error { color: red; font-weight: 600; }
        .box { border: 2px solid GrayText; padding: .75rem 1rem; margin: 1rem 0; }
        .box.error { border-color: red; }
        code, pre { background: color-mix(in srgb, CanvasText 8%, Canvas); padding: .15rem .35rem; word-break: break-all; white-space: pre-wrap; }
        label { display: block; margin-top: .75rem; font-weight: 600; }
        input[type=text], input[type=email], input[type=password], input[type=date] { width: 100%; max-width: 26rem; padding: .4rem; }
        button { padding: .5rem 1rem; margin-top: 1rem; font-size: 1rem; }
        ol.steps { padding-left: 1.2rem; color: GrayText; } ol.steps .now { color: CanvasText; font-weight: 700; }
    </style>
</head>
<body>
<h1>Einrichtung des VDBS Vereinsportals</h1>
@hasSection('step')
    <ol class="steps">
        @foreach (['check' => 'Voraussetzungen', 'key' => 'Schlüssel', 'database' => 'Datenbank', 'admin' => 'Erste Administration', 'finish' => 'Abschluss'] as $key => $label)
            <li @class(['now' => $key === trim($__env->yieldContent('step'))])>{{ $label }}</li>
        @endforeach
    </ol>
@endif
@yield('content')
</body>
</html>
