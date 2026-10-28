<p>Paket <strong>Content</strong> · <code>&lt;x-vdbs.content.feature&gt;</code> · Vorbilder: FU Berlin, Oodi, VÖBB</p>
<p>Ein Baustein für Text mit Bild und Aktionen in vier Varianten. Die Bildquelle ist wie überall Pflicht; <code>tone</code> färbt das Band bzw. die Textfläche.</p>

<h2>side · Text und großes Bild</h2>
<x-vdbs.content.feature variant="side" title="Forschung" image="{{ placeholder('split', 1) }}" image-alt="" caption="Platzhalter-Unterschrift zum Bild">
    <p>Die Forschung zeichnet sich durch die Vielfalt ihrer Netzwerke aus. Dieser Text steht schmal links, das Bild nimmt zwei Drittel der Breite ein.</p>
    <x-slot:actions><x-vdbs.actions.button icon="chevron-right">Zum Forschungsprofil</x-vdbs.actions.button></x-slot:actions>
</x-vdbs.content.feature>

<h2>side · graues Band, Bild links, zwei Aktionen</h2>
<x-vdbs.content.feature variant="side" tone="surface" :reverse="true" media-size="m" title="Studium" image="{{ placeholder('split', 2) }}" image-alt="">
    <p><strong>Willkommen zum neuen Semester!</strong> Zum Start begrüßen wir alle neuen und zurückkehrenden Mitglieder.</p>
    <x-slot:actions>
        <x-vdbs.actions.button variant="secondary" shape="outline" icon="chevron-right">Angebot entdecken</x-vdbs.actions.button>
        <x-vdbs.actions.button variant="secondary" shape="outline" icon="chevron-right">Fortbildung</x-vdbs.actions.button>
    </x-slot:actions>
</x-vdbs.content.feature>

<h2>overlap · Fläche überlappt das Bild</h2>
<x-vdbs.content.feature variant="overlap" tone="soft" title="Was kann man im Portal tun?" image="{{ placeholder('split', 3) }}" image-alt="">
    <p>Das Portal ist genau das, was du dir wünschst: Informationen finden, Dokumente teilen und Kolleginnen und Kollegen erreichen.</p>
    <x-slot:actions><x-vdbs.actions.button variant="secondary" shape="outline">Mehr lesen</x-vdbs.actions.button></x-slot:actions>
</x-vdbs.content.feature>

<h2>offset · dunkle Fläche, Bild ragt heraus</h2>
<x-vdbs.content.feature variant="offset" tone="dark" eyebrow="Die digitalen Angebote" title="Wir bringen das Portal zu Ihnen nach Hause!" image="{{ placeholder('split', 4) }}" image-alt="" :reverse="false">
    <p>Mit Ihrem Zugang haben Sie Zugriff auf alle Bereiche, Dokumente und Neuigkeiten.</p>
    <x-slot:actions><x-vdbs.actions.button>Zu den Angeboten</x-vdbs.actions.button></x-slot:actions>
</x-vdbs.content.feature>

<h2>links · Überschrift links, Aktionen rechts</h2>
<x-vdbs.content.feature variant="links" tone="surface" title="Haltung und Verantwortung">
    <p>Der Verein steht seit seiner Gründung für Wahrheit, Gerechtigkeit und Freiheit.</p>
    <x-slot:actions>
        <x-vdbs.actions.button icon="chevron-right">Die politische Arbeit</x-vdbs.actions.button>
        <x-vdbs.actions.button variant="secondary" shape="outline" icon="chevron-right">Maßnahmen gegen Diskriminierung</x-vdbs.actions.button>
        <x-vdbs.actions.button variant="secondary" shape="outline" icon="chevron-right">Infos zur geplanten Neuordnung</x-vdbs.actions.button>
    </x-slot:actions>
</x-vdbs.content.feature>

<h2>Farbflächen (tone)</h2>
<x-vdbs.content.feature variant="links" tone="soft" title="soft"><p>Ruhige Hinweisfläche.</p></x-vdbs.content.feature>
<x-vdbs.content.feature variant="links" tone="dark" title="dark"><p>Lila Fläche, Schrift in Weiß.</p><x-slot:actions><x-vdbs.actions.button>Aktion</x-vdbs.actions.button></x-slot:actions></x-vdbs.content.feature>
<x-vdbs.content.feature variant="links" tone="brand" title="brand"><p>Grüne Markenfläche, Schrift in Ink.</p><x-slot:actions><x-vdbs.actions.button>Aktion</x-vdbs.actions.button></x-slot:actions></x-vdbs.content.feature>
