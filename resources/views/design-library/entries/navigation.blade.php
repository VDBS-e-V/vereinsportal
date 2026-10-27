<h2>Hauptnavigation</h2>
<div class="vdbs-header" style="padding:var(--space-2) var(--space-4)">
    <x-vdbs.nav-main :items="[
        ['label' => 'Start', 'href' => '#', 'icon' => 'house', 'current' => true],
        ['label' => 'Konto', 'href' => '#', 'icon' => 'user'],
        ['label' => 'Vorstand', 'href' => '#', 'icon' => 'landmark'],
    ]" />
</div>
<h2>Breadcrumb</h2>
<x-vdbs.breadcrumb :items="[['label' => 'Start', 'href' => '#'], ['label' => 'Konto', 'href' => '#'], ['label' => 'Profil']]" />
