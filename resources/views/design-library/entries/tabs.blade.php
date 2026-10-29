<p>Paket <strong>Navigation</strong> · <code>&lt;x-vdbs.navigation.tab-nav&gt;</code> (Tabs als Links), <code>&lt;x-vdbs.navigation.tabs&gt;</code> mit <code>&lt;x-vdbs.navigation.tab&gt;</code> (Inhalt auf derselben Seite)</p>

<h2>Tabs als Links</h2>
<p>Jeder Tab ist eine eigene Seite, zum Beispiel im Konto.</p>
<x-vdbs.navigation.tab-nav label="Konto" :items="[
    ['label' => 'Profil', 'href' => '#', 'current' => true, 'icon' => 'user'],
    ['label' => 'Sicherheit', 'href' => '#', 'icon' => 'shield'],
    ['label' => 'Benachrichtigungen', 'href' => '#', 'icon' => 'bell', 'badge' => '3'],
    ['label' => 'Sitzungen', 'href' => '#'],
]" />

<h2>Tabs mit Inhalt</h2>
<p>Mit JavaScript eine Tab-Leiste (Pfeiltasten, Pos1/Ende, <code>#tab-b</code> in der Adresse wählt einen Tab), ohne JavaScript stehen die Inhalte mit Überschriften untereinander.</p>
<x-vdbs.navigation.tabs label="Beispiel-Tabs" active="tab-b">
    <x-vdbs.navigation.tab id="tab-a" label="Übersicht"><p>Inhalt der Übersicht.</p></x-vdbs.navigation.tab>
    <x-vdbs.navigation.tab id="tab-b" label="Mitglieder"><p>Inhalt der Mitglieder.</p></x-vdbs.navigation.tab>
    <x-vdbs.navigation.tab id="tab-c" label="Dokumente"><p>Inhalt der Dokumente.</p></x-vdbs.navigation.tab>
</x-vdbs.navigation.tabs>
