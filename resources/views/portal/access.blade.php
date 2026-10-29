@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Zugang zum Portal']];
@endphp

<x-layouts.app title="Zugang zum Portal" width="medium">
    <x-vdbs.layout.page-header title="Zugang zum Portal" lead="So melden Sie sich an oder erhalten Ihr Konto." :breadcrumb="$crumbs" />

    <x-vdbs.content.prose>
        <h2>Sie haben bereits ein Konto</h2>
        <p>Melden Sie sich mit Ihrer E-Mail-Adresse und Ihrem Passwort an. Haben Sie eine Zwei-Faktor-Anmeldung eingerichtet, geben Sie danach den Code ein.</p>
        <h2>Sie haben noch kein Konto</h2>
        <p>Konten erhalten Mitglieder, Teamende und Schulen vom Verein. Die Geschäftsstelle legt Ihr Konto an und sendet Ihnen eine Einladung per E-Mail. Über den Link in der E-Mail legen Sie Ihr Passwort fest. Der Link ist sieben Tage gültig. Wenn Sie noch keine Einladung erhalten haben, wenden Sie sich bitte an uns.</p>
    </x-vdbs.content.prose>

    <p style="margin-top:var(--space-6)">
        @auth
            <x-vdbs.actions.button :href="route('account.profile')" icon="user">Zu Ihrem Konto</x-vdbs.actions.button>
        @else
            <x-vdbs.actions.button :href="route('login')" icon="log-in">Zur Anmeldung</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('password.request')">Passwort vergessen</x-vdbs.actions.button>
        @endauth
        <x-vdbs.actions.button variant="tertiary" :href="route('portal.contact')">Kontakt aufnehmen</x-vdbs.actions.button>
    </p>
</x-layouts.app>
