<p>Paket <strong>Karten</strong> · <code>&lt;x-vdbs.cards.contact-block&gt;</code></p>
<x-vdbs.layout.section variant="band">
    <x-vdbs.cards.contact-block
        title="Kontakt"
        text="Bei Fragen oder Problemen helfen wir gerne weiter."
        :lines="[
            ['label' => 'Support', 'value' => 'support@example.org', 'href' => 'mailto:support@example.org'],
            ['label' => 'Kontakt', 'value' => 'kontakt@example.org', 'href' => 'mailto:kontakt@example.org'],
        ]"
    >
        <x-slot:media><x-vdbs.media.figure :src="placeholder('contact')" alt="" /></x-slot:media>
        <x-slot:actions><x-vdbs.actions.link-button href="#">Zum Kontaktformular</x-vdbs.actions.link-button></x-slot:actions>
    </x-vdbs.cards.contact-block>
</x-vdbs.layout.section>

<h3>Mit grauer Fläche (<code>surface</code>)</h3>
<x-vdbs.cards.contact-block :surface="true" title="Kontakt" text="Sie erreichen uns unter den E-Mail-Adressen:" :lines="[['label' => 'Support', 'value' => 'support@example.org', 'href' => 'mailto:support@example.org']]">
    <x-slot:media><x-vdbs.media.figure :src="placeholder('contact')" alt="" /></x-slot:media>
    <x-slot:actions><x-vdbs.actions.link-button href="#">Zum Kontaktformular</x-vdbs.actions.link-button></x-slot:actions>
</x-vdbs.cards.contact-block>
