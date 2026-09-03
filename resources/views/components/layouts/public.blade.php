<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'VDB Portal' }}</title>

    @livewireStyles

    <style>
        body {
            margin: 0;
            font-family: system-ui, sans-serif;
            background: #f5f5f5;
            color: #171717;
        }

        main {
            width: min(100% - 2rem, 42rem);
            margin: 4rem auto;
        }

        .card {
            background: white;
            padding: 2rem;
            border: 1px solid #ddd;
            border-radius: 0.75rem;
        }

        .field {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            margin-bottom: 0.35rem;
            font-weight: 600;
        }

        input:not([type="checkbox"]) {
            box-sizing: border-box;
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #aaa;
            border-radius: 0.4rem;
        }

        button {
            padding: 0.75rem 1rem;
        }
    </style>
</head>

<body>
    <main>
        {{ $slot }}
    </main>

    @livewireScripts
</body>

</html>