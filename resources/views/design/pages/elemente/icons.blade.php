@extends('design.layout')

@section('title', 'Icons')

@section('content')
    @php
        $iconGroups = [
            'Navigation' => [
                'home',
                'menu',
                'close',
                'chevron-down',
                'chevron-left',
                'chevron-right',
                'arrow-left',
                'arrow-right',
                'external-link',
            ],
            'Aktionen' => [
                'search',
                'filter',
                'plus',
                'edit',
                'trash',
                'download',
                'upload',
                'copy',
                'print',
                'refresh',
                'sort',
                'more-horizontal',
            ],
            'Inhalte' => [
                'file',
                'calendar',
                'clock',
                'location',
                'mail',
                'ticket',
            ],
            'Status & Konto' => [
                'check',
                'alert-triangle',
                'info',
                'help',
                'lock',
                'eye',
                'user',
                'settings',
                'login',
                'logout',
            ],
        ];
    @endphp

    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente</p>
            <h1 class="page-title__title">Icons</h1>
            <p class="page-title__lead">
                Symbole werden zentral über <code>&lt;x-vdbs.icon&gt;</code>
                ausgegeben. Sie unterstützen Beschriftungen, ersetzen sie aber
                bei wichtigen Aktionen nicht.
            </p>
        </header>

        @foreach ($iconGroups as $group => $icons)
            <section class="{{ $loop->first ? 'stack' : 'section stack' }}">
                <h2>{{ $group }}</h2>

                <div class="design-token-list">
                    @foreach ($icons as $icon)
                        <div class="design-token-row">
                            <div class="icon-label">
                                <x-vdbs.icon :name="$icon" size="24" :label="$icon" />
                                <strong>{{ $icon }}</strong>
                            </div>
                            <code>&lt;x-vdbs.icon name="{{ $icon }}" /&gt;</code>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="section stack">
            <h2>Accessibility</h2>

            <div class="design-example stack">
                <p>
                    Dekorative Icons erhalten kein zugängliches Label und sind
                    mit <code>aria-hidden</code> aus dem Accessibility Tree entfernt.
                </p>

                <p class="icon-label">
                    <x-vdbs.icon name="calendar" />
                    <span>12.11.2026</span>
                </p>

                <p>
                    Ein alleinstehendes informatives Symbol benötigt dagegen ein
                    explizites <code>label</code>.
                </p>

                <x-vdbs.icon name="info" size="24" label="Weitere Informationen" />
            </div>
        </section>
    </div>
@endsection
