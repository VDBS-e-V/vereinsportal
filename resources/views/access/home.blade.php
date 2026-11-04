@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Persönlicher Zugang']];
    $value = fn (string $field) => $draft[$field] ?? $contact->{$field};
@endphp

<x-layouts.app title="Offene Seminare" width="wide">
    <x-vdbs.layout.page-header title="Offene Seminare für Sie" lead="Angemeldet als {{ $contact->email }}. Der Zugang gilt nur für Bewerbungen auf Seminare." :breadcrumb="$crumbs">
        <x-slot:actions>
            <form method="post" action="{{ route('access.logout') }}">@csrf
                <x-vdbs.actions.button type="submit" variant="secondary" icon="log-out">Abmelden</x-vdbs.actions.button>
            </form>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="2" align="start">
        <section aria-labelledby="open-heading">
            <h2 id="open-heading" style="font-size:var(--font-size-h3)">Seminare mit Teambedarf</h2>
            @if ($seminars->isEmpty())
                <x-vdbs.feedback.empty-state title="Zurzeit sucht kein Seminar ein Team" icon="calendar">
                    <p>Sobald ein Seminar Teamende sucht, erscheint es hier und Sie bekommen eine Nachricht.</p>
                </x-vdbs.feedback.empty-state>
            @else
                <x-vdbs.data.table caption="Offene Seminare" :columns="[['key' => 'title', 'label' => 'Seminar'], ['key' => 'where', 'label' => 'Ort'], ['key' => 'date', 'label' => 'Erster Termin'], ['key' => 'deadline', 'label' => 'Bewerbung bis']]">
                    @foreach ($seminars as $seminar)
                        <tr>
                            <td>{{ $seminar->number }} {{ $seminar->title }}<br><span class="vdbs-hint">{{ $seminar->type->label() }}</span></td>
                            <td>{{ $seminar->partner?->city ?? '–' }}</td>
                            <td>{{ $seminar->sessions->first()?->starts_at->format('d.m.Y') ?? '–' }}</td>
                            <td>{{ $seminar->application_deadline?->format('d.m.Y') ?? '–' }}</td>
                        </tr>
                    @endforeach
                </x-vdbs.data.table>
                <p class="vdbs-hint">Die Bewerbung selbst folgt im nächsten Schritt.</p>
            @endif
        </section>

        <section aria-labelledby="profile-heading">
            <h2 id="profile-heading" style="font-size:var(--font-size-h3)">Ihre Angaben</h2>
            <p>Für eine Bewerbung brauchen wir Ihren Namen. Telefon und Anschrift sind freiwillig; die Anschrift brauchen wir erst für einen Vertrag.</p>
            <x-vdbs.forms.form :action="route('access.profile')">
                <x-vdbs.forms.form-grid :columns="2">
                    <x-vdbs.forms.input name="first_name" label="Vorname" :required="true" :value="$value('first_name')" autocomplete="given-name" maxlength="100" />
                    <x-vdbs.forms.input name="last_name" label="Nachname" :required="true" :value="$value('last_name')" autocomplete="family-name" maxlength="100" />
                    <x-vdbs.forms.input name="phone" type="tel" label="Telefon" :optional="true" :value="$value('phone')" autocomplete="tel" maxlength="50" />
                </x-vdbs.forms.form-grid>
                <x-vdbs.forms.form-grid :columns="2">
                    <x-vdbs.forms.input name="street" label="Straße" :optional="true" :value="$value('street')" autocomplete="address-line1" maxlength="150" />
                    <x-vdbs.forms.input name="house_number" label="Hausnummer" :optional="true" :value="$value('house_number')" maxlength="20" />
                    <x-vdbs.forms.input name="postal_code" label="PLZ" :optional="true" :value="$value('postal_code')" autocomplete="postal-code" maxlength="10" />
                    <x-vdbs.forms.input name="city" label="Ort" :optional="true" :value="$value('city')" autocomplete="address-level2" maxlength="100" />
                </x-vdbs.forms.form-grid>
                <x-vdbs.forms.checkbox name="save" label="Für die Zukunft speichern" hint="Ohne Häkchen gelten die Angaben nur für diesen Besuch und werden nicht gespeichert." :checked="$contact->profile_saved" />
                <x-slot:actions>
                    <x-vdbs.actions.button type="submit" icon="save">Angaben übernehmen</x-vdbs.actions.button>
                </x-slot:actions>
            </x-vdbs.forms.form>
        </section>
    </x-vdbs.layout.grid>
</x-layouts.app>
