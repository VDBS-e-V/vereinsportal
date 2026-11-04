@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Persönlicher Zugang']];
    $value = fn (string $field) => $draft[$field] ?? $contact->{$field};
    $hasName = filled($value('first_name')) && filled($value('last_name'));
    $selectable = $seminars->reject(fn ($seminar): bool => $mineBySeminar->has($seminar->id) && $mineBySeminar[$seminar->id]->status !== \App\Modules\Seminars\Enums\ApplicationStatus::Withdrawn);
    $withdrawable = $mine->filter(fn ($application): bool => $application->status === \App\Modules\Seminars\Enums\ApplicationStatus::Applied);
@endphp

<x-layouts.app title="Offene Seminare" width="wide">
    <x-vdbs.layout.page-header title="Offene Seminare für Sie" lead="Angemeldet als {{ $contact->email }}. Der Zugang gilt nur für Bewerbungen auf Seminare." :breadcrumb="$crumbs">
        <x-slot:actions>
            <form method="post" action="{{ route('access.logout') }}">@csrf
                <x-vdbs.actions.button type="submit" variant="secondary" icon="log-out">Abmelden</x-vdbs.actions.button>
            </form>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    <section aria-labelledby="open-heading">
        <h2 id="open-heading" style="font-size:var(--font-size-h3)">Seminare mit Teambedarf</h2>
        @if ($seminars->isEmpty())
            <x-vdbs.feedback.empty-state title="Zurzeit sucht kein Seminar ein Team" icon="calendar">
                <p>Sobald ein Seminar Teamende sucht, erscheint es hier und Sie bekommen eine Nachricht.</p>
            </x-vdbs.feedback.empty-state>
        @else
            <p>Haken Sie ein oder mehrere Seminare an und bewerben Sie sich unten mit einem Klick.</p>
            <x-vdbs.data.table caption="Offene Seminare" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'apply-form'], ['key' => 'title', 'label' => 'Seminar'], ['key' => 'where', 'label' => 'Ort'], ['key' => 'date', 'label' => 'Erster Termin'], ['key' => 'deadline', 'label' => 'Bewerbung bis'], ['key' => 'state', 'label' => 'Ihr Stand']]">
                @foreach ($seminars as $seminar)
                    @php($own = $mineBySeminar->get($seminar->id))
                    <tr>
                        <td class="vdbs-table__select">
                            @if ($selectable->contains('id', $seminar->id))
                                <span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="seminar_ids[]" value="{{ $seminar->id }}" form="apply-form" aria-label="{{ $seminar->title }} auswählen"></span>
                            @endif
                        </td>
                        <td>{{ $seminar->number }} {{ $seminar->title }}<br><span class="vdbs-hint">{{ $seminar->type->label() }}</span></td>
                        <td>{{ $seminar->partner?->city ?? '–' }}</td>
                        <td>{{ $seminar->sessions->first()?->starts_at->format('d.m.Y') ?? '–' }}</td>
                        <td>{{ $seminar->application_deadline?->format('d.m.Y') ?? '–' }}</td>
                        <td>@if ($own)<x-vdbs.data.badge :tone="$own->status->tone()">{{ $own->status->label() }}</x-vdbs.data.badge>@else –@endif</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="apply-form">Nichts ausgewählt.</p>

            @if ($selectable->isNotEmpty())
                <x-vdbs.forms.form :action="route('access.apply')" id="apply-form" style="margin-top:var(--space-4)" :summary="false">
                    <x-vdbs.forms.form-section title="Ihre Bewerbung">
                        @unless ($hasName)
                            <x-vdbs.feedback.alert variant="warning">Tragen Sie zuerst unter „Ihre Angaben“ Ihren Vor- und Nachnamen ein.</x-vdbs.feedback.alert>
                        @endunless
                        <x-vdbs.forms.multi-select name="competences" label="Ihre Kompetenzen" :searchable="true" placeholder="Kompetenzen suchen" search-label="Kompetenzen filtern" :optional="true" :options="$competenceOptions" :value="$contact->competences ?? []" hint="Mehrere möglich. Sie helfen der Koordination beim Zusammenstellen des Teams." />
                        <x-vdbs.forms.textarea name="experience" label="Erfahrung" :optional="true" :rows="3" maxlength="800" :counter="true" hint="Kurz: Was haben Sie schon in Schule oder Jugendarbeit gemacht?" />
                        <x-vdbs.forms.form-grid :columns="2">
                            <x-vdbs.forms.input name="birth_year" type="number" label="Geburtsjahr" :optional="true" min="1930" max="{{ (int) now()->format('Y') - 14 }}" hint="Freiwillig, nur für die Teamzusammenstellung der Koordination." />
                            <x-vdbs.forms.input name="phone" type="tel" label="Telefon" :optional="true" :value="$value('phone')" maxlength="50" autocomplete="tel" />
                        </x-vdbs.forms.form-grid>
                        <x-vdbs.forms.input name="note" label="Nachricht an die Koordination" :optional="true" maxlength="300" />
                        <x-vdbs.forms.checkbox name="wants_lead" label="Ich möchte gern die Seminarleitung übernehmen." />
                        <x-vdbs.forms.checkbox name="consent_contact" label="Mein Name und meine Kontaktdaten dürfen an das Team weitergegeben werden." hint="Freiwillig. Ohne Häkchen sehen Teammitglieder nur Ihren Namen." />
                        <x-vdbs.forms.checkbox name="consent_data" label="Ich stimme zu, dass meine Angaben zur Zusammenstellung des Teams verarbeitet werden." :required="true" />
                        <x-vdbs.forms.checkbox name="save" label="Kompetenzen und Telefon für die Zukunft speichern" hint="Ohne Häkchen gelten die Angaben nur für diese Bewerbung." />
                    </x-vdbs.forms.form-section>
                    <x-slot:actions>
                        <x-vdbs.actions.button type="submit" icon="check" :disabled="! $hasName">Auf die ausgewählten Seminare bewerben</x-vdbs.actions.button>
                    </x-slot:actions>
                </x-vdbs.forms.form>
            @endif
        @endif
    </section>

    @if ($mine->isNotEmpty())
        <section aria-labelledby="mine-heading" style="margin-top:var(--space-8)">
            <h2 id="mine-heading" style="font-size:var(--font-size-h3)">Ihre Bewerbungen</h2>
            <x-vdbs.data.table caption="Ihre Bewerbungen" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'withdraw-form'], ['key' => 'title', 'label' => 'Seminar'], ['key' => 'state', 'label' => 'Stand']]">
                @foreach ($mine as $application)
                    <tr>
                        <td class="vdbs-table__select">
                            @if ($withdrawable->contains('id', $application->id))
                                <span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="application_ids[]" value="{{ $application->id }}" form="withdraw-form" aria-label="Bewerbung auf {{ $application->seminar->title }} auswählen"></span>
                            @endif
                        </td>
                        <td>{{ $application->seminar->number }} {{ $application->seminar->title }}</td>
                        <td><x-vdbs.data.badge :tone="$application->status->tone()">{{ $application->status->label() }}</x-vdbs.data.badge></td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            @if ($withdrawable->isNotEmpty())
                <form id="withdraw-form" method="post" action="{{ route('access.withdraw') }}" style="margin-top:var(--space-4)">@csrf
                    <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="withdraw-form">Nichts ausgewählt.</p>
                    <x-vdbs.actions.button type="submit" variant="secondary" icon="x">Ausgewählte Bewerbungen zurückziehen</x-vdbs.actions.button>
                </form>
            @endif
        </section>
    @endif

    <section aria-labelledby="profile-heading" style="margin-top:var(--space-8)">
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
</x-layouts.app>
