@php
    $primitive = [
        'Marke' => ['gruen', 'gruen-dunkel', 'gruen-dunkler', 'gruen-hell', 'gruen-leuchtend', 'gruen-pastell', 'ink', 'weiss', 'lila', 'marine'],
        'Grau' => ['grau-hell', 'grau-linie', 'grau-rahmen', 'grau-text'],
        'Status' => ['fehler', 'fehler-dunkel', 'fehler-hell', 'fehler-leuchtend', 'warnung-hell', 'warnung-rand', 'warnung-text', 'warnung-leuchtend'],
        'Dark Mode' => ['aubergine-950', 'aubergine-900', 'aubergine-800', 'aubergine-700', 'mauve-500', 'mauve-400', 'nebel'],
    ];
    $semantic = [
        'Flächen' => ['bg-page', 'bg-surface', 'bg-accent-soft', 'bg-header-footer', 'bg-brand', 'bg-action', 'bg-action-hover', 'bg-media', 'bg-danger'],
        'Status-Flächen' => ['bg-error-soft', 'bg-warning-soft'],
        'Rahmen und Akzente' => ['border-default', 'border-input', 'border-error', 'border-warning', 'border-success', 'accent-info', 'accent-success', 'accent-warning'],
        'Gruppen' => ['group-1', 'group-2', 'group-3', 'group-4', 'group-5', 'group-6'],
    ];
    $text = ['text-default', 'text-muted', 'text-link', 'text-link-hover', 'text-error', 'text-success', 'text-warning', 'text-on-dark', 'text-on-brand', 'text-on-action', 'text-on-danger', 'text-org'];
@endphp

<p>Die Tokens haben drei Ebenen: <strong>Primitive</strong> (die festen Werte, nur dort stehen Farbcodes), <strong>Semantik</strong> (Rollen, verweisen nur auf Primitive) und lokale <strong>Komponenten-Variablen</strong>. Der Dark Mode überschreibt nur die Semantik. Details: <code>docs/36_TOKENS.md</code>, Quelle: <code>resources/css/vdbs/tokens.css</code>.</p>

<x-vdbs.feedback.alert variant="info">Im Code nur semantische Tokens verwenden, nie Primitive oder Farbcodes. Schrift auf hellem Markengrün (<code>--bg-brand</code>) ist Ink, Schrift auf dem dunklen Aktionsgrün (<code>--bg-action</code>) ist Weiß. Ein Test (<code>TokensTest</code>) prüft das.</x-vdbs.feedback.alert>

<h2>Primitive · die festen Werte</h2>
@foreach ($primitive as $group => $keys)
    <h3>{{ $group }}</h3>
    <div style="display:flex;flex-wrap:wrap;gap:var(--space-4)">
        @foreach ($keys as $key)
            <div><div style="width:6rem;height:3rem;background:var(--color-{{ $key }});border:1px solid var(--border-input)"></div><code>--color-{{ $key }}</code></div>
        @endforeach
    </div>
@endforeach
<h3>Gruppenfarben</h3>
<div style="display:flex;flex-wrap:wrap;gap:var(--space-4)">
    @foreach (range(1, 6) as $n)
        <div><div style="width:6rem;height:3rem;background:var(--color-group-{{ $n }})"></div><code>--color-group-{{ $n }}</code></div>
    @endforeach
</div>

<h2>Semantik · Rollen im Code</h2>
@foreach ($semantic as $group => $keys)
    <h3>{{ $group }}</h3>
    <div style="display:flex;flex-wrap:wrap;gap:var(--space-4)">
        @foreach ($keys as $token)
            <div><div style="width:6rem;height:3rem;background:var(--{{ $token }});border:1px solid var(--border-input)"></div><code>--{{ $token }}</code></div>
        @endforeach
    </div>
@endforeach

<h3>Schrift</h3>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(14rem,1fr));gap:var(--space-3)">
    @foreach ($text as $token)
        @php $dark = in_array($token, ['text-on-dark', 'text-on-brand', 'text-on-action', 'text-on-danger'], true); $bg = match ($token) { 'text-on-dark' => 'bg-header-footer', 'text-on-brand' => 'bg-brand', 'text-on-action' => 'bg-action', 'text-on-danger' => 'bg-danger', default => 'bg-page' }; @endphp
        <div style="background:var(--{{ $bg }});color:var(--{{ $token }});padding:var(--space-3);border:1px solid var(--border-default)"><strong>Aa</strong> <code style="color:inherit">--{{ $token }}</code></div>
    @endforeach
</div>

<h2>Aktion und Markengrün</h2>
<p>Die primäre Aktion (Buttons, Login, Pfeilflächen) nutzt das dunkle Grün mit weißer Schrift. Das helle Markengrün bleibt für Akzente und Hover-Flächen mit Ink-Schrift.</p>
<div style="display:flex;gap:var(--space-4);flex-wrap:wrap">
    <x-vdbs.actions.button>Aktion</x-vdbs.actions.button>
    <div class="vdbs-on-brand" style="background:var(--bg-brand);padding:var(--space-3)"><x-vdbs.actions.button>Auf Markengrün</x-vdbs.actions.button></div>
</div>
