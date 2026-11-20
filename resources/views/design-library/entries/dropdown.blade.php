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

<h2>Profilmenü</h2>
<p>Mit Kopf (Avatar, Name, Rolle) und Gruppen, die durch <code>&lt;hr class="vdbs-dropdown__divider"&gt;</code> getrennt sind; die Klasse <code>vdbs-dropdown--profile</code> macht das Menü breiter.</p>
<x-vdbs.navigation.dropdown label="Erika Mustermann" align="start" class="vdbs-dropdown--profile">
    <div class="vdbs-dropdown__head">
        <x-vdbs.data.avatar name="Erika Mustermann" size="m" aria-hidden="true" />
        <div>
            <p class="vdbs-dropdown__head-name">Erika Mustermann</p>
            <p class="vdbs-dropdown__head-meta"><x-vdbs.icon name="user" :size="16" />Teamende</p>
        </div>
    </div>
    <hr class="vdbs-dropdown__divider">
    <x-vdbs.navigation.dropdown-item href="#" icon="user">Mein Profil</x-vdbs.navigation.dropdown-item>
    <x-vdbs.navigation.dropdown-item href="#" icon="settings">Kontoeinstellungen</x-vdbs.navigation.dropdown-item>
    <x-vdbs.navigation.dropdown-item href="#" icon="ticket">Meine Tickets</x-vdbs.navigation.dropdown-item>
    <hr class="vdbs-dropdown__divider">
    <x-vdbs.navigation.dropdown-item href="#" icon="mail">Kontakt</x-vdbs.navigation.dropdown-item>
    <x-vdbs.navigation.dropdown-item href="#" icon="book-open">FAQ</x-vdbs.navigation.dropdown-item>
    <x-vdbs.navigation.dropdown-item href="#" icon="circle-help">Hilfe</x-vdbs.navigation.dropdown-item>
    <hr class="vdbs-dropdown__divider">
    <x-vdbs.navigation.dropdown-item href="#" icon="log-out" class="vdbs-dropdown__item--logout">Abmelden</x-vdbs.navigation.dropdown-item>
</x-vdbs.navigation.dropdown>
