<p>Paket <strong>Karten</strong> · <code>&lt;x-vdbs.cards.quick-links&gt;</code> mit <code>&lt;x-vdbs.cards.quick-link&gt;</code></p>
<x-vdbs.cards.quick-links label="Schnellzugriff">
    <x-vdbs.cards.quick-link label="Über das Portal" href="#" :tone="4" />
    <x-vdbs.cards.quick-link label="Zugang zum Portal" href="#" :tone="1" />
    <x-vdbs.cards.quick-link label="Mein Konto" href="#" :tone="3" icon="user" />
    <x-vdbs.cards.quick-link label="Hilfe" href="#" :tone="6" icon="info" />
</x-vdbs.cards.quick-links>
<p>Die Gruppenfarbe (<code>tone</code> 1–6) ist nur ein Akzent; Schrift auf Gruppenfarben ist immer Ink.</p>

<h3>Variante <code>solid</code></h3>
<p>Ganze Kachel in der Gruppenfarbe, z. B. direkt unter dem Hero der Startseite. Die Schrift ist Ink, bei den Gruppen 3 und 5 weiß.</p>
<x-vdbs.cards.quick-links label="Schnellzugriff (farbig)" variant="solid">
    <x-vdbs.cards.quick-link label="Über das Portal" href="#" :tone="3" />
    <x-vdbs.cards.quick-link label="Zugang zum Portal" href="#" :tone="5" />
    <x-vdbs.cards.quick-link label="Mein Konto" href="#" :tone="1" />
    <x-vdbs.cards.quick-link label="Hilfe" href="#" :tone="6" />
</x-vdbs.cards.quick-links>
