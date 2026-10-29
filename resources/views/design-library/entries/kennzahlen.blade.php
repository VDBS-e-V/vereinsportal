<p>Paket <strong>Daten</strong> · <code>&lt;x-vdbs.data.stat&gt;</code> im Raster <code>&lt;x-vdbs.layout.grid&gt;</code></p>

<x-vdbs.layout.grid :columns="4">
    <x-vdbs.data.stat label="Aktive Nutzer" value="128" icon="users" tone="success" trend="up" trend-label="+12 seit letztem Monat" />
    <x-vdbs.data.stat label="Offene Einladungen" value="7" icon="mail" hint="Älteste vor 9 Tagen" />
    <x-vdbs.data.stat label="Ohne Zwei-Faktor" value="23" icon="shield" tone="warning" trend="down" trend-label="−4 seit letzter Woche" href="#" />
    <x-vdbs.data.stat label="Gesperrte Konten" value="2" icon="lock" tone="error" trend="flat" trend-label="unverändert" />
</x-vdbs.layout.grid>
