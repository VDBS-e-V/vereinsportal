<h2>Spacing</h2>
@foreach ([1, 2, 3, 4, 5, 6, 8, 10, 12] as $n)
    <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-1)"><div style="width:var(--space-{{ $n }});height:1rem;background:var(--bg-brand)"></div><code>--space-{{ $n }}</code></div>
@endforeach
<h2>Radius</h2>
<p>Ecken sind <code>0</code> (bewusste Abweichung vom Styleguide). <code>--radius-full</code> bleibt für Avatare und Pills.</p>
<div style="display:flex;gap:var(--space-4)">
    <div style="width:5rem;height:5rem;background:var(--bg-surface);border:2px solid var(--border-input);border-radius:var(--radius-md)"></div>
    <div style="width:5rem;height:5rem;background:var(--bg-surface);border:2px solid var(--border-input);border-radius:var(--radius-full)"></div>
</div>
<h2>Schatten</h2>
<div style="display:flex;gap:var(--space-5);padding:var(--space-4)">
    @foreach (['sm', 'md', 'lg'] as $s)
        <div style="width:6rem;height:4rem;background:var(--bg-page);box-shadow:var(--shadow-{{ $s }})"></div>
    @endforeach
</div>
