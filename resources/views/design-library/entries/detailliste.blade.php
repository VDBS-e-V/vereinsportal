<p>Paket <strong>Daten</strong> · <code>&lt;x-vdbs.data.detail-list&gt;</code>, <code>&lt;x-vdbs.data.detail-item&gt;</code></p>

<h2>Übereinander, zwei Spalten</h2>
<x-vdbs.data.detail-list :columns="2">
    <x-vdbs.data.detail-item label="Name">Erika Mustermann</x-vdbs.data.detail-item>
    <x-vdbs.data.detail-item label="E-Mail" href="mailto:erika@example.org">erika@example.org</x-vdbs.data.detail-item>
    <x-vdbs.data.detail-item label="Rolle"><x-vdbs.data.badge tone="success">Aktiv</x-vdbs.data.badge> Vorstand</x-vdbs.data.detail-item>
    <x-vdbs.data.detail-item label="Telefon"></x-vdbs.data.detail-item>
</x-vdbs.data.detail-list>

<h2>Nebeneinander (inline)</h2>
<x-vdbs.data.detail-list layout="inline">
    <x-vdbs.data.detail-item label="Mitglied seit">12.03.2021</x-vdbs.data.detail-item>
    <x-vdbs.data.detail-item label="Bereich">Vorstand, Mitglieder</x-vdbs.data.detail-item>
    <x-vdbs.data.detail-item label="Letzte Anmeldung">28.10.2026, 14:32</x-vdbs.data.detail-item>
    <x-vdbs.data.detail-item label="Zwei-Faktor"></x-vdbs.data.detail-item>
</x-vdbs.data.detail-list>
