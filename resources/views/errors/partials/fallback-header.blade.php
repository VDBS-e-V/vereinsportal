{{-- Reduzierte Kopfzeile: nur wenn der normale Portal-Header nicht gebaut werden kann. --}}
<header class="error-shell__header vdbs-error-shell__header">
    <div class="error-shell__header-inner vdbs-error-shell__header-inner">
        <a
            class="error-shell__brand vdbs-error-shell__brand"
            href="{{ url('/') }}"
            aria-label="VDBS Startseite"
        >
            <img
                src="{{ asset('images/brand/vdbs-bildmarke-breit.png') }}"
                alt=""
                aria-hidden="true"
            >
            <span>VDBS Portal</span>
        </a>

        <span class="error-shell__context vdbs-error-shell__context">
            Sichere Orientierung bei technischen Fehlern
        </span>
    </div>
</header>
