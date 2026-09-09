@extends('design.layout')

@section('title', 'Code-Vorlagen')

@section('content')
    @php
        /*
         * Beispielcode wird absichtlich aus Teilstrings aufgebaut.
         *
         * Würden Blade-Echos oder <x-vdbs.*>-Tags wörtlich innerhalb
         * eines dynamischen Component-Attributs stehen, versucht der
         * Blade-Compiler sie als echten Template-Code zu interpretieren.
         */
        $bladeEchoOpen = '{'.'{';
        $bladeEchoClose = '}'.'}';
        $componentOpen = '<'.'x-vdbs.';
        $componentClose = '</'.'x-vdbs.';

        $primaryActionCode = implode(PHP_EOL, [
            '<a class="btn" href="'.$bladeEchoOpen." route('my.home') ".$bladeEchoClose.'">',
            '    Zur Übersicht',
            '</a>',
        ]);

        $noticeCode = implode(PHP_EOL, [
            $componentOpen.'notice type="warning" role="alert">',
            '    Ihre Sitzung läuft bald ab.',
            $componentClose.'notice>',
        ]);

        $iconCode =
            $componentOpen.'icon name="calendar" size="20" />';
    @endphp

    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Bibliothek · Wiederverwendung</p>
            <h1 class="page-title__title">Code-Vorlagen</h1>
            <p class="page-title__lead">
                Beispiele zeigen immer die gerenderte Variante und den
                dazugehörigen Blade-Code. Der Code kann direkt kopiert und
                anschließend an den konkreten Fachkontext angepasst werden.
            </p>
        </header>

        <x-vdbs.code-example
            title="Primäre Aktion"
            :code="$primaryActionCode"
        >
            <a class="btn" href="#">
                Zur Übersicht
            </a>
        </x-vdbs.code-example>

        <x-vdbs.code-example
            title="Warnhinweis"
            :code="$noticeCode"
        >
            <x-vdbs.notice type="warning" role="alert">
                Ihre Sitzung läuft bald ab.
            </x-vdbs.notice>
        </x-vdbs.code-example>

        <x-vdbs.code-example
            title="Icon"
            :code="$iconCode"
            compact
        >
            <span class="icon-label">
                <x-vdbs.icon name="calendar" size="20" />
                Veranstaltung
            </span>
        </x-vdbs.code-example>
    </div>
@endsection
