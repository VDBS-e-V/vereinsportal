@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Veranstaltungen', 'href' => route('events.public.index')], ['label' => $event->title]];
    $left = $event->spotsLeft();
@endphp

<x-layouts.app :title="$event->title" width="medium">
    <x-vdbs.layout.page-header :title="$event->title" :breadcrumb="$crumbs">
        <x-slot:meta><x-vdbs.data.badge :tone="$event->status->tone()">{{ $event->status->label() }}</x-vdbs.data.badge></x-slot:meta>
    </x-vdbs.layout.page-header>

    @if ($preview)
        <x-vdbs.feedback.alert variant="warning">Vorschau: Diese Veranstaltung ist noch nicht öffentlich sichtbar.</x-vdbs.feedback.alert>
    @endif

    @if (filled($event->description))
        <p style="white-space:pre-line">{{ $event->description }}</p>
    @endif

    <x-vdbs.data.detail-list layout="inline">
        @if ($event->location)<x-vdbs.data.detail-item label="Ort">{{ $event->location }}</x-vdbs.data.detail-item>@endif
        <x-vdbs.data.detail-item label="Termine">
            @forelse ($event->sessions as $session){{ $session->period() }}@if ($session->location), {{ $session->location }}@endif<br>@empty wird noch bekannt gegeben @endforelse
        </x-vdbs.data.detail-item>
        @if ($event->registration_deadline)<x-vdbs.data.detail-item label="Anmeldeschluss">{{ $event->registration_deadline->format('d.m.Y, H:i') }} Uhr</x-vdbs.data.detail-item>@endif
        @if ($left !== null)<x-vdbs.data.detail-item label="Freie Plätze">{{ $left }}</x-vdbs.data.detail-item>@endif
    </x-vdbs.data.detail-list>

    <section aria-labelledby="register-heading" style="margin-top:var(--space-8)">
        <h2 id="register-heading" style="font-size:var(--font-size-h3)">Anmeldung</h2>

        @if ($event->acceptsRegistrations())
            <p>Nach dem Absenden bekommst du eine E-Mail mit einem Bestätigungslink. Erst mit der Bestätigung ist deine Anmeldung gültig. Danach schicken wir dir eine Anmeldebestätigung mit deinem Ticketcode. Für die Erlaubnis zur Teilnahme bist du selbst verantwortlich.</p>

            <x-vdbs.forms.form :action="route('events.public.register', $event->slug)">
                <x-vdbs.forms.bot-guard />
                <x-vdbs.forms.form-grid :columns="2">
                    <x-vdbs.forms.input name="name" label="Vor- und Nachname" :required="true" maxlength="150" autocomplete="name" />
                    <x-vdbs.forms.input name="school" label="Schule" :required="true" maxlength="200" />
                    <x-vdbs.forms.input name="class_name" label="Klasse" :required="true" maxlength="40" />
                    <x-vdbs.forms.input name="birth_date" label="Geburtsdatum" type="date" :required="true" autocomplete="bday" />
                    <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" maxlength="254" autocomplete="email" hint="Dorthin schicken wir den Bestätigungslink und dein Ticket." />
                </x-vdbs.forms.form-grid>
                <x-vdbs.forms.checkbox name="consent" :required="true" label="Ich bin einverstanden, dass meine Angaben zur Durchführung der Veranstaltung gespeichert werden. Sie werden drei Jahre nach der Veranstaltung gelöscht. Mehr steht in der Datenschutzerklärung (siehe Fußzeile)." />

                <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Anmelden</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @else
            <x-vdbs.feedback.alert variant="info">
                @if ($event->isFull()) Die Veranstaltung ist leider ausgebucht.
                @elseif ($event->deadlinePassed()) Der Anmeldeschluss ist vorbei.
                @elseif ($event->status === \App\Modules\Events\Enums\EventStatus::Done) Die Veranstaltung hat stattgefunden. Deine <a href="{{ route('events.public.certificate') }}">Teilnahmebestätigung</a> kannst du hier abrufen.
                @else Die Anmeldung ist zurzeit nicht offen. @endif
            </x-vdbs.feedback.alert>
        @endif
    </section>
</x-layouts.app>
