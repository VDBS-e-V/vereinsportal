<p>Paket <strong>Navigation</strong> · <code>&lt;x-vdbs.navigation.pagination&gt;</code></p>

<h2>Mitte einer langen Liste</h2>
<x-vdbs.navigation.pagination :current="7" :last="20" href="#seite-:page" />

<h2>Erste Seite</h2>
<x-vdbs.navigation.pagination :current="1" :last="5" href="#seite-:page" />

<h2>Letzte Seite</h2>
<x-vdbs.navigation.pagination :current="13" :last="13" href="#seite-:page" />

<p>Mit einem Laravel-Paginator: <code>&lt;x-vdbs.navigation.pagination :paginator="$users" /&gt;</code>. Bei nur einer Seite erscheint nichts.</p>
