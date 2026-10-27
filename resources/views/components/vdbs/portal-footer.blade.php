<footer class="vdbs-footer">
    <div class="vdbs-container">
        <div class="vdbs-footer__columns">
            @foreach (config('portal.footer') as $column)
                <nav aria-labelledby="footer-col-{{ $loop->index }}">
                    <h2 id="footer-col-{{ $loop->index }}" class="vdbs-footer__heading">{{ $column['heading'] }}</h2>
                    <ul class="vdbs-footer__list">
                        @foreach ($column['links'] as $link)
                            <li>
                                <a href="{{ $link['url'] }}" @class(['vdbs-footer__link--icon' => ! empty($link['icon'])])>
                                    @if (! empty($link['icon'])) <x-vdbs.icon :name="$link['icon']" :size="18" /> @endif
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                        @if ($loop->index === 1)
                            @auth
                                <li><a href="{{ route('design-library.index') }}">Design-Bibliothek</a></li>
                            @else
                                <li><a href="{{ route('login') }}">Anmelden</a></li>
                            @endauth
                        @endif
                    </ul>
                </nav>
            @endforeach

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
