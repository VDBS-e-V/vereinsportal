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
                ['label' => 'Startseite', 'href' => route('my.home')],
                ['label' => 'Kontakt', 'href' => route('portal.contact')],
                ['label' => 'Impressum', 'href' => route('portal.imprint')],
                ['label' => 'Datenschutz', 'href' => route('portal.privacy')],
                ['label' => 'Barrierefreiheit', 'href' => route('portal.accessibility')],
                ['label' => 'Beratung', 'href' => route('portal.contact')],
            ],
        ],
        [
            'title' => 'Soziale Medien',
            'links' => [
                ['label' => 'Instagram', 'href' => '#', 'social' => '◎'],
                ['label' => 'Homo Politicus', 'href' => '#', 'social' => '▶'],
            ],
        ],
    ];

    $footerLogo = null;

    foreach ([
        'images/brand/vdbs-bildmarke-kompakt.png',
        'images/brand/vdbs-logo.png',
    ] as $candidate) {
        if (file_exists(public_path($candidate))) {
            $footerLogo = asset($candidate);
            break;
        }
    }
@endphp

<footer class="vdbs-footer" aria-labelledby="vdbs-footer-heading">
    <h2 id="vdbs-footer-heading" class="vdbs-visually-hidden">Weitere Informationen</h2>

    <div class="vdbs-footer__top">
        <x-vdbs.frame width="normal" gutter="both">
            <div class="vdbs-footer__rule" aria-hidden="true"></div>

            <div class="vdbs-footer__grid">
                @foreach ($footerColumns as $column)
                    <section class="vdbs-footer__column">
                        <h3 class="vdbs-footer__heading">{{ $column['title'] }}</h3>

                        <ul class="vdbs-footer__links" role="list">
                            @foreach ($column['links'] as $link)
                                <li>
                                    <a href="{{ $link['href'] }}">
                                        @if (isset($link['social']))
                                            <span class="vdbs-footer__social-icon" aria-hidden="true">{{ $link['social'] }}</span>
                                        @endif
                                        <span>{{ $link['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>

            <div class="vdbs-footer__brand-rule" aria-hidden="true"></div>

            <div class="vdbs-footer__bottom-inner">
                <div class="vdbs-footer__brand">
                    @if ($footerLogo !== null)
                        <img class="vdbs-footer__brand-logo" src="{{ $footerLogo }}" alt="VDBS">
                    @else
                        <div class="vdbs-footer__brand-mark" aria-hidden="true">VDBS</div>
                    @endif

                    <div class="vdbs-footer__brand-copy">
                        <p class="vdbs-footer__association">
                            Verband für Demokratiebildung und Bibliotheken an Schulen e.V.
                        </p>
                        <p class="vdbs-footer__claim">Weil Schule uns alle angeht!</p>
                    </div>
                </div>
            </div>
        </x-vdbs.frame>
    </div>
</footer>
