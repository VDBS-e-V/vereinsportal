@props([
    'homeUrl' => '#',
    'logoUrl' => null,
    'links' => [],
])

@php
    $logoUrl ??= asset('images/brand/vdbs-logo.png');
@endphp

<footer class="site-footer vdbs-site-footer" role="contentinfo">
    <div class="site-footer__inner vdbs-site-footer__inner">
        <div class="site-footer__top">
            <a class="site-footer__brand link--no-style" href="{{ $homeUrl }}">
                <span class="site-footer__logo-crop" aria-hidden="true">
                    <img src="{{ $logoUrl }}" alt="">
                </span>

                <span class="site-footer__brand-copy">
                    <strong>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</strong>
                    <span>Weil Schule uns alle angeht!</span>
                </span>
            </a>

            @if ($links !== [])
                <nav aria-label="Fußnavigation">
                    <ul class="site-footer__links">
                        @foreach ($links as $link)
                            @if (($link['url'] ?? null) !== null)
                                <li>
                                    <a href="{{ $link['url'] }}">
                                        {{ $link['label'] }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>

        <div class="site-footer__meta">
            &copy; {{ now()->year }} VDBS e. V. · Vereinsportal
        </div>
    </div>
</footer>
