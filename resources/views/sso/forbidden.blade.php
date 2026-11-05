<x-layouts.app title="Anmeldung nicht möglich" width="narrow">
    <x-vdbs.layout.page-header title="Für diese Anwendung nicht freigegeben" lead="{{ $name ? 'Ihr Konto darf sich bei „'.$name.'“ nicht anmelden.' : 'Ihr Konto darf sich hier nicht anmelden.' }}" />
    <p>Wenn Sie Zugang brauchen, wenden Sie sich an die Geschäftsstelle des Vereins.</p>
</x-layouts.app>
