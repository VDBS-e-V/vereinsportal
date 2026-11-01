<x-account.shell title="Darstellung" current="appearance" lead="Hell, dunkel oder wie Ihr Gerät. Die Einstellung gilt nur in diesem Browser.">
    <fieldset class="vdbs-fieldset" data-vdbs-theme-choice>
        <legend>Farbschema</legend>
        <x-vdbs.actions.button-group label="Farbschema">
            <x-vdbs.actions.button variant="secondary" type="button" data-vdbs-theme-set="system" icon="settings">Wie das Gerät</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" type="button" data-vdbs-theme-set="light" icon="sun">Hell</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" type="button" data-vdbs-theme-set="dark" icon="moon">Dunkel</x-vdbs.actions.button>
        </x-vdbs.actions.button-group>
    </fieldset>
    <p class="vdbs-js-only" style="margin-top:var(--space-4)">Aktuell: <strong data-vdbs-theme-current>Wie das Gerät</strong></p>
    <noscript><p>Für diese Einstellung braucht der Browser JavaScript. Sie können das Farbschema auch im Fuß der Seite wechseln.</p></noscript>
</x-account.shell>
