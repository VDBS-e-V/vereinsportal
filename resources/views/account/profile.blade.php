<x-account.shell title="Profil" current="profile" lead="Ihre Stammdaten im Verein.">
    @if ($person === null)
        <x-vdbs.feedback.alert variant="info">Ihr Konto ist keiner Person zugeordnet. Wenden Sie sich an die Geschäftsstelle, wenn Ihre Stammdaten angezeigt werden sollen.</x-vdbs.feedback.alert>

        <x-vdbs.data.detail-list layout="inline">
            <x-vdbs.data.detail-item label="E-Mail-Adresse">{{ $user->email }}</x-vdbs.data.detail-item>
        </x-vdbs.data.detail-list>
    @else
        <h2>Stammdaten</h2>
        <p>Name und Geburtsdatum pflegt die Geschäftsstelle. Bei Änderungen wenden Sie sich bitte dorthin.</p>

        <x-vdbs.data.detail-list layout="inline">
            <x-vdbs.data.detail-item label="Name">{{ $person->fullName() }}</x-vdbs.data.detail-item>
            <x-vdbs.data.detail-item label="Geburtsdatum">{{ $person->birth_date?->format('d.m.Y') }}</x-vdbs.data.detail-item>
            <x-vdbs.data.detail-item label="E-Mail-Adresse" href="{{ route('account.email') }}">{{ $user->email }}</x-vdbs.data.detail-item>
        </x-vdbs.data.detail-list>

        @if ($membership)
            @php $state = $membership->effectiveStatus(); @endphp
            <h2 style="margin-top:var(--space-8)">Mitgliedschaft</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Status">{{ $state->label() }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Antrag vom">{{ $membership->requested_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Mitglied seit">{{ $membership->starts_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Letzter Tag der Mitgliedschaft">{{ $membership->ends_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        @endif

        <h2 style="margin-top:var(--space-8)">Kontakt und Anschrift</h2>

        <x-vdbs.forms.form :action="route('account.profile.update')" method="put" :summary="false">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="phone" label="Telefon" type="tel" :value="old('phone', $person->phone)" :optional="true" autocomplete="tel" />
                <div></div>
                <x-vdbs.forms.input name="street" label="Straße" :value="old('street', $person->street)" :optional="true" autocomplete="address-line1" />
                <x-vdbs.forms.input name="house_number" label="Hausnummer" :value="old('house_number', $person->house_number)" :optional="true" />
                <x-vdbs.forms.input name="postal_code" label="Postleitzahl" :value="old('postal_code', $person->postal_code)" :optional="true" autocomplete="postal-code" />
                <x-vdbs.forms.input name="city" label="Ort" :value="old('city', $person->city)" :optional="true" autocomplete="address-level2" />
                <x-vdbs.forms.input name="country_code" label="Land (Kürzel)" hint="Zwei Buchstaben, z. B. DE" :value="old('country_code', $person->country_code)" :required="true" autocomplete="country" />
            </x-vdbs.forms.form-grid>

            <x-slot:actions>
                <x-vdbs.actions.button type="submit">Speichern</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>
    @endif
</x-account.shell>
