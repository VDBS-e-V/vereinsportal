<div style="display:flex;flex-wrap:wrap;gap:var(--space-4)">
    @foreach (range(1, 6) as $n)
        <x-vdbs.group-mark :number="$n" />
    @endforeach
</div>
<p>Marken bestehen immer aus Form, Farbe und Zahl; die Bedeutung hängt nie an der Farbe allein.</p>
