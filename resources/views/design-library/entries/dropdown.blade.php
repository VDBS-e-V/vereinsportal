<p>Paket <strong>Navigation</strong> · <code>&lt;x-vdbs.navigation.dropdown&gt;</code>, <code>&lt;x-vdbs.navigation.dropdown-item&gt;</code></p>
<p>Gebaut auf <code>&lt;details&gt;</code>: funktioniert ohne JavaScript; mit JavaScript schließen Esc und ein Klick daneben das Menü.</p>

<div style="display:flex;gap:var(--space-8);flex-wrap:wrap;min-height:14rem">
    <x-vdbs.navigation.dropdown label="Aktionen">
        <x-vdbs.navigation.dropdown-item href="#" icon="pencil">Bearbeiten</x-vdbs.navigation.dropdown-item>
        <x-vdbs.navigation.dropdown-item href="#" icon="copy">Duplizieren</x-vdbs.navigation.dropdown-item>
        <hr>
        <x-vdbs.navigation.dropdown-item icon="trash-2">Löschen</x-vdbs.navigation.dropdown-item>
    </x-vdbs.navigation.dropdown>

    <x-vdbs.navigation.dropdown variant="plain" align="end" label="Nutzermenü">
        <x-slot:trigger>
            <x-vdbs.data.avatar name="Erika Mustermann" size="s" aria-hidden="true" />
            <span>Erika Mustermann</span>
        </x-slot:trigger>
        <x-vdbs.navigation.dropdown-item href="#" icon="user">Mein Konto</x-vdbs.navigation.dropdown-item>
        <x-vdbs.navigation.dropdown-item href="#" icon="settings">Einstellungen</x-vdbs.navigation.dropdown-item>
        <x-vdbs.navigation.dropdown-item icon="log-out">Abmelden</x-vdbs.navigation.dropdown-item>
    </x-vdbs.navigation.dropdown>
</div>

<h2>Avatar</h2>
<div style="display:flex;gap:var(--space-4);align-items:center">
    <x-vdbs.data.avatar name="Erika Mustermann" size="s" />
    <x-vdbs.data.avatar name="Erika Mustermann" />
    <x-vdbs.data.avatar name="Max" size="l" />
</div>
