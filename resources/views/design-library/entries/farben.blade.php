@php
    $brand = ['gruen' => 'Grün', 'ink' => 'Ink', 'weiss' => 'Weiß', 'lila' => 'Lila'];
@endphp
<h2>Markenfarben</h2>
<div style="display:flex;flex-wrap:wrap;gap:var(--space-4)">
    @foreach ($brand as $key => $name)
        <div><div style="width:6rem;height:4rem;background:var(--color-{{ $key }});border:1px solid var(--border-input)"></div><code>--color-{{ $key }}</code></div>
    @endforeach
</div>
<h2>Gruppenfarben</h2>
<div style="display:flex;flex-wrap:wrap;gap:var(--space-4)">
    @foreach (range(1, 6) as $n)
        <div><div style="width:6rem;height:4rem;background:var(--color-group-{{ $n }})"></div><code>--color-group-{{ $n }}</code></div>
    @endforeach
</div>
<h2>Semantische Tokens</h2>
<x-vdbs.alert variant="info">Im Code ausschließlich diese Tokens verwenden, nie die Rohfarben. Schrift auf Grün ist immer Ink, nie Weiß.</x-vdbs.alert>
<div style="display:flex;flex-wrap:wrap;gap:var(--space-4)">
    @foreach (['bg-page', 'bg-surface', 'bg-accent-soft', 'bg-header-footer', 'bg-brand'] as $token)
        <div><div style="width:6rem;height:4rem;background:var(--{{ $token }});border:1px solid var(--border-input)"></div><code>--{{ $token }}</code></div>
    @endforeach
</div>
