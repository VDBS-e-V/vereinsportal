@php
    $editingPerson = $person ?? null;
    $linkedUser = $editingPerson?->user;
@endphp

<div class="stack stack--lg">
    <section class="panel stack">
        <header class="stack stack--sm">
            <h2>Persönliche Daten</h2>
            <p>Pflichtfelder sind Vorname, Nachname und Geburtsdatum.</p>
        </header>

        <div class="form__grid form__grid--2">
            <div class="form__field">
                <label class="form__label" for="person-title">Titel</label>
                <input class="form__control" id="person-title" name="title" type="text" maxlength="50" autocomplete="honorific-prefix" value="{{ old('title', $editingPerson?->title) }}" @error('title') aria-invalid="true" aria-describedby="person-title-error" @enderror>
                @error('title')<p class="form__error" id="person-title-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form__field">
                <label class="form__label" for="person-first-name">Vorname</label>
                <input class="form__control" id="person-first-name" name="first_name" type="text" maxlength="100" autocomplete="given-name" required value="{{ old('first_name', $editingPerson?->first_name) }}" @error('first_name') aria-invalid="true" aria-describedby="person-first-name-error" @enderror>
                @error('first_name')<p class="form__error" id="person-first-name-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form__field">
                <label class="form__label" for="person-name-addition">Namenszusatz</label>
                <input class="form__control" id="person-name-addition" name="name_addition" type="text" maxlength="100" value="{{ old('name_addition', $editingPerson?->name_addition) }}" @error('name_addition') aria-invalid="true" aria-describedby="person-name-addition-error" @enderror>
                @error('name_addition')<p class="form__error" id="person-name-addition-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form__field">
                <label class="form__label" for="person-last-name">Nachname</label>
                <input class="form__control" id="person-last-name" name="last_name" type="text" maxlength="100" autocomplete="family-name" required value="{{ old('last_name', $editingPerson?->last_name) }}" @error('last_name') aria-invalid="true" aria-describedby="person-last-name-error" @enderror>
                @error('last_name')<p class="form__error" id="person-last-name-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form__field">
                <label class="form__label" for="person-birth-date">Geburtsdatum</label>
                <input class="form__control" id="person-birth-date" name="birth_date" type="date" autocomplete="bday" required value="{{ old('birth_date', $editingPerson?->birth_date?->format('Y-m-d')) }}" @error('birth_date') aria-invalid="true" aria-describedby="person-birth-date-error" @enderror>
                @error('birth_date')<p class="form__error" id="person-birth-date-error" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="panel stack">
        <header class="stack stack--sm">
            <h2>Kontakt</h2>
            @if ($linkedUser !== null)
                <p>Die E-Mail-Adresse ist mit einem Benutzerkonto verknüpft und kann hier nicht geändert werden.</p>
            @endif
        </header>
        <div class="form__grid form__grid--2">
            <div class="form__field">
                <label class="form__label" for="person-email">E-Mail-Adresse</label>
                <input class="form__control" id="person-email" name="email" type="email" maxlength="254" autocomplete="email" required value="{{ old('email', $editingPerson?->email) }}" @readonly($linkedUser !== null) @error('email') aria-invalid="true" aria-describedby="person-email-error" @enderror>
                @if ($linkedUser !== null)<p class="form__help">E-Mail-Änderungen für verknüpfte Konten laufen über den gesicherten Konto-E-Mail-Prozess.</p>@endif
                @error('email')<p class="form__error" id="person-email-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form__field">
                <label class="form__label" for="person-phone">Telefonnummer</label>
                <input class="form__control" id="person-phone" name="phone" type="tel" maxlength="50" autocomplete="tel" value="{{ old('phone', $editingPerson?->phone) }}" @error('phone') aria-invalid="true" aria-describedby="person-phone-error" @enderror>
                @error('phone')<p class="form__error" id="person-phone-error" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="panel stack">
        <header class="stack stack--sm"><h2>Adresse</h2></header>
        <div class="form__grid form__grid--2">
            <div class="form__field">
                <label class="form__label" for="person-street">Straße</label>
                <input class="form__control" id="person-street" name="street" type="text" maxlength="150" autocomplete="address-line1" value="{{ old('street', $editingPerson?->street) }}" @error('street') aria-invalid="true" aria-describedby="person-street-error" @enderror>
                @error('street')<p class="form__error" id="person-street-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form__field">
                <label class="form__label" for="person-house-number">Hausnummer</label>
                <input class="form__control" id="person-house-number" name="house_number" type="text" maxlength="30" value="{{ old('house_number', $editingPerson?->house_number) }}" @error('house_number') aria-invalid="true" aria-describedby="person-house-number-error" @enderror>
                @error('house_number')<p class="form__error" id="person-house-number-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form__field">
                <label class="form__label" for="person-postal-code">Postleitzahl</label>
                <input class="form__control" id="person-postal-code" name="postal_code" type="text" maxlength="20" autocomplete="postal-code" value="{{ old('postal_code', $editingPerson?->postal_code) }}" @error('postal_code') aria-invalid="true" aria-describedby="person-postal-code-error" @enderror>
                @error('postal_code')<p class="form__error" id="person-postal-code-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form__field">
                <label class="form__label" for="person-city">Ort</label>
                <input class="form__control" id="person-city" name="city" type="text" maxlength="120" autocomplete="address-level2" value="{{ old('city', $editingPerson?->city) }}" @error('city') aria-invalid="true" aria-describedby="person-city-error" @enderror>
                @error('city')<p class="form__error" id="person-city-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form__field">
                <label class="form__label" for="person-country-code">Ländercode</label>
                <input class="form__control" id="person-country-code" name="country_code" type="text" maxlength="2" autocomplete="country" required value="{{ old('country_code', $editingPerson?->country_code ?? 'DE') }}" @error('country_code') aria-invalid="true" aria-describedby="person-country-code-error" @enderror>
                <p class="form__help">Zweistelliger ISO-Ländercode, zum Beispiel DE.</p>
                @error('country_code')<p class="form__error" id="person-country-code-error" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <div class="page-title__actions">
        <button class="btn" type="submit">{{ $submitLabel }}</button>
        <a class="btn btn--quiet" href="{{ $cancelUrl }}">Abbrechen</a>
    </div>
</div>
