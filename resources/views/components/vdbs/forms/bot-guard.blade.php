{{--
    Schutz vor automatisierten Eingaben (Paket "forms"). Gehört in jedes öffentliche Formular, dessen Route die
    Middleware "bot.guard" trägt. Gibt ein signiertes Token, ein unsichtbares Köderfeld und das Feld für die
    Rechenaufgabe aus, die resources/js/bot-guard.js im Hintergrund löst. Ohne JavaScript lässt sich das Formular
    nicht absenden; der Hinweis dazu steht im <noscript>. Bei portal.bot_guard.enabled = false entfällt alles.
--}}
@php
    $guard = app(\App\Support\BotGuard\BotGuard::class);
@endphp

@if ($guard->enabled())
    <div class="vdbs-bot-guard" data-vdbs-bot-guard data-bits="{{ $guard->bits() }}" @unless (config('portal.bot_guard.proof_of_work', true)) data-pow="off" @endunless>
        @if (session('bot_guard_failed'))
            <x-vdbs.feedback.alert variant="error" title="Prüfung nicht bestanden">Die Prüfung gegen automatisierte Eingaben ist fehlgeschlagen. Bitte versuchen Sie es erneut. Ihre Eingaben wurden übernommen.</x-vdbs.feedback.alert>
        @endif

        <div class="vdbs-bot-guard__trap" aria-hidden="true">
            <label>Bitte leer lassen <input type="text" name="{{ \App\Support\BotGuard\BotGuard::HONEYPOT_FIELD }}" value="" tabindex="-1" autocomplete="off"></label>
        </div>
        <input type="hidden" name="{{ \App\Support\BotGuard\BotGuard::TOKEN_FIELD }}" value="{{ $guard->issue() }}">
        <input type="hidden" name="{{ \App\Support\BotGuard\BotGuard::PROOF_FIELD }}" value="" data-vdbs-bot-guard-proof>
        <p class="vdbs-bot-guard__status" role="status" data-vdbs-bot-guard-status></p>
        <noscript><p class="vdbs-bot-guard__notice">Zum Schutz vor automatisierten Eingaben benötigt dieses Formular JavaScript. Bitte aktivieren Sie es oder schreiben Sie uns eine E-Mail.</p></noscript>
    </div>
@endif
