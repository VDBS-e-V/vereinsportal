@php
    $systems = collect(config('portal.systems'))->filter(fn (array $system): bool => filled($system['url']));
@endphp

<footer class="vdbs-footer">
    <div class="vdbs-container">
        <div class="vdbs-footer__columns">
            <nav aria-labelledby="footer-portal">
                <h2 id="footer-portal" class="vdbs-footer__heading">Portal</h2>
                <ul class="vdbs-footer__list">
                    @auth
                        <li><a href="{{ route('home') }}">Startseite</a></li>
                        <li><a href="{{ route('design-library.index') }}">Design-Bibliothek</a></li>
                    @else
                        <li><a href="{{ route('login') }}">Anmelden</a></li>
                    @endauth
                </ul>
            </nav>

            @auth
                <nav aria-labelledby="footer-areas">
                    <h2 id="footer-areas" class="vdbs-footer__heading">Bereiche</h2>
                    <ul class="vdbs-footer__list">
                        @foreach ([\App\Support\PortalAreas::Start, \App\Support\PortalAreas::Konto] as $area)
                            <li><a href="{{ $area === \App\Support\PortalAreas::Start ? route('home') : '#' }}">{{ $area->label() }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endauth

            @if ($systems->isNotEmpty())
                <nav aria-labelledby="footer-systems">
                    <h2 id="footer-systems" class="vdbs-footer__heading">Externe Systeme</h2>
                    <ul class="vdbs-footer__list">
                        @foreach ($systems as $system)
                            <li><a href="{{ $system['url'] }}">{{ $system['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            <div>
                <h2 class="vdbs-footer__heading">Diese Seite</h2>
                <ul class="vdbs-footer__list">
                    <li><button type="button" class="vdbs-footer__action" onclick="window.print()">Drucken</button></li>
                    <li><button type="button" class="vdbs-footer__action" data-vdbs-theme-toggle>Darstellung wechseln</button></li>
                </ul>
            </div>
        </div>

        <div class="vdbs-footer__bottom">
            <img class="vdbs-footer__logo" src="{{ asset('images/brand/vdbs-logo.png') }}" alt="" width="128" height="128">
            <div>
                <p class="vdbs-footer__org">Verband für Demokratiebildung und Bibliotheken an Schulen e.&nbsp;V.</p>
                <p class="vdbs-footer__claim">Weil Schule uns alle angeht!</p>
            </div>
        </div>
    </div>
</footer>
