<p>Paket <strong>Navigation</strong> · <code>&lt;x-vdbs.navigation.side-nav&gt;</code> in <code>&lt;x-vdbs.layout.aside-layout variant="sidebar"&gt;</code></p>

<x-vdbs.layout.aside-layout variant="sidebar" side="start" size="s" aside-label="Konto">
    <h2 style="margin-top:0">Profil</h2>
    <p>Inhalt der gewählten Seite.</p>
    <x-slot:aside>
        <x-vdbs.navigation.side-nav label="Konto" :sections="[
            ['title' => 'Mein Konto', 'items' => [
                ['label' => 'Profil', 'href' => '#', 'icon' => 'user', 'current' => true],
                ['label' => 'Sicherheit', 'href' => '#', 'icon' => 'shield'],
                ['label' => 'Benachrichtigungen', 'href' => '#', 'icon' => 'bell', 'badge' => '3'],
            ]],
            ['title' => 'Verwaltung', 'items' => [
                ['label' => 'Nutzer', 'href' => '#', 'icon' => 'users'],
                ['label' => 'Rollen', 'href' => '#', 'icon' => 'key-round'],
            ]],
        ]" />
    </x-slot:aside>
</x-vdbs.layout.aside-layout>
