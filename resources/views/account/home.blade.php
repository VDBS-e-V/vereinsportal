<x-account.shell title="Konto" current="home" lead="Ihr Profil, Ihre Stammdaten und Ihre Einstellungen an einem Ort.">
    <x-vdbs.layout.grid :columns="2">
        @if ($hasProfile)
            <section aria-labelledby="profile-heading">
                <h2 id="profile-heading" style="font-size:var(--font-size-h3)">Profil</h2>
                <p>Das zeigen Sie anderen: Anzeigename, Foto und Kurzvorstellung. Standardmäßig ist alles nur für Sie sichtbar.</p>
                <p>Vollständigkeit: <strong>{{ $completeness }} %</strong></p>
                <x-vdbs.actions.button-group>
                    <x-vdbs.actions.button :href="route('account.profile.edit')" icon="pencil">Profil bearbeiten</x-vdbs.actions.button>
                    <x-vdbs.actions.button variant="secondary" :href="route('account.profile')" icon="eye">Mein Profil ansehen</x-vdbs.actions.button>
                </x-vdbs.actions.button-group>
            </section>
        @endif

        <section aria-labelledby="account-heading">
            <h2 id="account-heading" style="font-size:var(--font-size-h3)">Konto</h2>
            <p>Das speichert das Portal über Sie: Stammdaten, Anmeldung, Sicherheit und Mitgliedschaft. Das sehen nur Sie.</p>

            @if ($hints === [])
                <p>Zu Ihrem Konto gibt es keine offenen Hinweise.</p>
            @else
                <ul>
                    @foreach ($hints as $hint)
                        <li><a href="{{ $hint['href'] }}">{{ $hint['text'] }}</a></li>
                    @endforeach
                </ul>
            @endif

            <p><a href="{{ route('account.data') }}">Stammdaten</a> · <a href="{{ route('account.security.index') }}">Zwei-Faktor</a> · <a href="{{ route('account.roles') }}">Mitgliedschaft</a></p>
        </section>
    </x-vdbs.layout.grid>
</x-account.shell>
