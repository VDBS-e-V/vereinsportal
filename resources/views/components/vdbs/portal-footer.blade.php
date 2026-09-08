@php
    $footerColumns = [
        [
            'title' => 'Informationen für',
            'links' => [
                ['label' => 'Teamer:innen', 'href' => '#'],
                ['label' => 'Vereinsmitglieder:innen', 'href' => '#'],
                ['label' => 'Verwaltung', 'href' => '#'],
                ['label' => 'Schüler:innen', 'href' => '#'],
                ['label' => 'Lehrkräfte', 'href' => '#'],
                ['label' => 'Partner:innen', 'href' => '#'],
            ],
        ],
        [
            'title' => 'Service-Portal',
            'links' => [
                ['label' => 'Startseite', 'href' => url('/')],
                ['label' => 'Über das Portal', 'href' => '#'],
                ['label' => 'Zugang zum Portal', 'href' => '#'],
                ['label' => 'FAQ', 'href' => '#'],
                ['label' => 'Kontakt', 'href' => '#'],
            ],
        ],
        [
            'title' => 'Externe Dienste',
            'links' => [
                ['label' => 'Bibliocollect', 'href' => '#'],
                ['label' => 'Webmail', 'href' => '#'],
                ['label' => 'Moodle', 'href' => '#'],
                ['label' => 'Nextcloud', 'href' => '#'],
                ['label' => 'MethodenMatrix', 'href' => '#'],
            ],
        ],
        [
            'title' => 'Diese Seite',
            'links' => [
                ['label' => 'Impressum', 'href' => '#'],
                ['label' => 'Datenschutz', 'href' => '#'],
                ['label' => 'Barrierefreiheit', 'href' => '#'],
                ['label' => 'Beratung', 'href' => '#'],
            ],
        ],
    ];

    $metaLinks = [
        ['label' => 'Kontakt', 'href' => '#'],
        ['label' => 'Impressum', 'href' => '#'],
        ['label' => 'Datenschutz', 'href' => '#'],
        ['label' => 'Barrierefreiheit', 'href' => '#'],
    ];

    $footerLogo = null;

    foreach ([
        'images/brand/vdbs-logo.png',
    ] as $candidate) {
        if (file_exists(public_path($candidate))) {
            $footerLogo = asset($candidate);
            break;
        }
    }
@endphp

<footer class="vdbs-footer" aria-labelledby="vdbs-footer-heading">
    <div class="vdbs-footer__top">
        <x-vdbs.frame width="wide" gutter="both">
            <div class="vdbs-footer__rule" aria-hidden="true"></div>

            <div class="vdbs-footer__grid">
                @foreach ($footerColumns as $column)
                    <section class="vdbs-footer__column">
                        <h2 id="{{ \Illuminate\Support\Str::slug($column['title']) }}-footer-heading"
                            class="vdbs-footer__heading">
                            {{ $column['title'] }}
                        </h2>

                        <ul class="vdbs-footer__links" role="list">
                            @foreach ($column['links'] as $link)
                                <li>
                                    <a href="{{ $link['href'] }}">
                                        <span>{{ $link['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        </x-vdbs.frame>
    </div>

    <div class="vdbs-footer__bottom">
        <x-vdbs.frame width="wide" gutter="both">
            <div class="vdbs-footer__bottom-inner">
                <div class="vdbs-footer__brand">
                    @if ($footerLogo !== null)
                        <img class="vdbs-footer__brand-logo" src="{{ $footerLogo }}" alt="VDBS">
                    @else
                        <div class="vdbs-footer__brand-mark" aria-hidden="true">
                            VDBS
                        </div>
                    @endif

                    <div class="vdbs-footer__brand-copy">
                        <p class="vdbs-footer__association">
                            Verband für Demokratiebildung und Bibliotheken an Schulen e.V.
                        </p>

                        <p class="vdbs-footer__claim">
                            Weil Schule uns alle angeht!
                        </p>
                    </div>
                </div>

                <nav class="vdbs-footer__meta" aria-label="Footer Meta Navigation">
                    @foreach ($metaLinks as $link)
                        <a href="{{ $link['href'] }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </x-vdbs.frame>
    </div>
</footer>