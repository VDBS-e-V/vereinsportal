<p>Zusammenspiel aus Aufmacher, Nachrichten-Teasern und Terminliste (Vorbild: FU Berlin Startseite).</p>

<x-vdbs.layout.aside-layout variant="sidebar" size="m" aside-label="Termine">
    <x-vdbs.cards.teaser variant="featured" title="Statement der Hochschulleitung zu queerfeindlichen Vorfällen" href="#" :image="placeholder('hero', 2)" />
    <x-vdbs.layout.grid :columns="2" align="start" style="margin-top: var(--space-5)">
        <x-vdbs.cards.teaser variant="flat" title="Vortrag | „Problemzonen. Zur Krise des liberalen Gesellschaftsmodells“" href="#" text="Öffentliche Einstein Lecture mit Soziologe Prof. Dr. Steffen Mau am 5. November um 18 Uhr." :image="placeholder('teaser', 1)" />
        <x-vdbs.cards.teaser variant="flat" title="Interview | „Wie wir miteinander an Lösungen arbeiten“" href="#" text="Ein Gespräch zum Amtsantritt der neuen Leitung am 1. November." :image="placeholder('teaser', 2)" />
    </x-vdbs.layout.grid>
    <x-slot:aside>
        <x-vdbs.data.event-list href="#">
            <x-vdbs.data.event-item date="29.10. – 30.10." title="Workshop | Echoes of Intelligence" href="#" />
            <x-vdbs.data.event-item date="01.11." time="13:00" title="Vortrag | The Making and Meaning of Politics" href="#" />
            <x-vdbs.data.event-item date="02.11." time="19:00" title="Vortrag | Künstlerische Interventionen" href="#" />
        </x-vdbs.data.event-list>
    </x-slot:aside>
</x-vdbs.layout.aside-layout>
