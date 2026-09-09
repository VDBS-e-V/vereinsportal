<?php

use App\Modules\Identity\Actions\Profile\UpdateProfileAction;
use App\Modules\Identity\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public ?string $title = null;

    public string $first_name = '';

    public ?string $name_addition = null;

    public string $last_name = '';

    public string $birth_date = '';

    public string $email = '';

    public ?string $phone = null;

    public ?string $street = null;

    public ?string $house_number = null;

    public ?string $postal_code = null;

    public ?string $city = null;

    public string $country_code = 'DE';

    public bool $saved = false;

    public function mount(): void
    {
        $user = auth()->user();

        if (!$user instanceof User) {
            return;
        }

        $person = $user->person;

        if ($person === null) {
            return;
        }

        $this->title = $person->title;
        $this->first_name = $person->first_name;
        $this->name_addition = $person->name_addition;
        $this->last_name = $person->last_name;

        $this->birth_date = $person->birth_date
            ->format('Y-m-d');

        $this->email = $person->email;
        $this->phone = $person->phone;
        $this->street = $person->street;
        $this->house_number = $person->house_number;
        $this->postal_code = $person->postal_code;
        $this->city = $person->city;

        $this->country_code =
            $person->country_code ?? 'DE';
    }

    public function save(
        UpdateProfileAction $updateProfile,
    ): void {
        $user = auth()->user();

        if (!$user instanceof User) {
            return;
        }

        $this->saved = false;

        $validated = $this->validate([
            'title' => [
                'nullable',
                'string',
                'max:50',
            ],
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],
            'name_addition' => [
                'nullable',
                'string',
                'max:100',
            ],
            'last_name' => [
                'required',
                'string',
                'max:100',
            ],
            'birth_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'street' => [
                'nullable',
                'string',
                'max:150',
            ],
            'house_number' => [
                'nullable',
                'string',
                'max:30',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'city' => [
                'nullable',
                'string',
                'max:120',
            ],
            'country_code' => [
                'required',
                'string',
                'size:2',
                'alpha',
            ],
        ]);

        $person = $updateProfile->execute(
            user: $user,
            values: $validated,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        );

        $this->country_code =
            $person->country_code;

        $this->saved = true;
    }
};

?>

<div class="portal-page">
    <header class="portal-page__header">
        <h1>Profil</h1>
    </header>

    @if ($saved)
        <x-vdbs.notice type="success" role="status">
            Ihre Profildaten wurden gespeichert.
        </x-vdbs.notice>
    @endif

    <form class="portal-page__form portal-page__form--wide form" wire:submit="save">
        <section class="portal-page__section">
            <div class="portal-page__section-header">
                <h2>Persönliche Daten</h2>
            </div>

            <div class="form__grid form__grid--2">
                <div class="form__field">
                    <label class="form__label" for="title">Titel</label>
                    <input
                        class="form__control"
                        id="title"
                        type="text"
                        wire:model="title"
                        autocomplete="honorific-prefix"
                        @error('title')
                            aria-invalid="true"
                            aria-describedby="profile-title-error"
                        @enderror
                    >
                    @error('title')
                        <p class="form__error" id="profile-title-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__field">
                    <label class="form__label" for="first_name">Vorname</label>
                    <input
                        class="form__control"
                        id="first_name"
                        type="text"
                        wire:model="first_name"
                        autocomplete="given-name"
                        required
                        @error('first_name')
                            aria-invalid="true"
                            aria-describedby="profile-first-name-error"
                        @enderror
                    >
                    @error('first_name')
                        <p class="form__error" id="profile-first-name-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__field">
                    <label class="form__label" for="name_addition">Namenszusatz</label>
                    <input
                        class="form__control"
                        id="name_addition"
                        type="text"
                        wire:model="name_addition"
                        @error('name_addition')
                            aria-invalid="true"
                            aria-describedby="profile-name-addition-error"
                        @enderror
                    >
                    @error('name_addition')
                        <p class="form__error" id="profile-name-addition-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__field">
                    <label class="form__label" for="last_name">Nachname</label>
                    <input
                        class="form__control"
                        id="last_name"
                        type="text"
                        wire:model="last_name"
                        autocomplete="family-name"
                        required
                        @error('last_name')
                            aria-invalid="true"
                            aria-describedby="profile-last-name-error"
                        @enderror
                    >
                    @error('last_name')
                        <p class="form__error" id="profile-last-name-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__field">
                    <label class="form__label" for="birth_date">Geburtsdatum</label>
                    <input
                        class="form__control"
                        id="birth_date"
                        type="date"
                        wire:model="birth_date"
                        autocomplete="bday"
                        required
                        @error('birth_date')
                            aria-invalid="true"
                            aria-describedby="profile-birth-date-error"
                        @enderror
                    >
                    @error('birth_date')
                        <p class="form__error" id="profile-birth-date-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section class="portal-page__section">
            <div class="portal-page__section-header">
                <h2>Kontakt</h2>
            </div>

            <div class="form__grid form__grid--2">
                <div class="form__field">
                    <label class="form__label" for="email">E-Mail-Adresse</label>
                    <input
                        class="form__control"
                        id="email"
                        type="email"
                        wire:model="email"
                        autocomplete="email"
                        readonly
                    >

                    <p class="form__help">
                        <a href="{{ route('my.email-change') }}">
                            E-Mail-Adresse ändern
                        </a>
                    </p>
                </div>

                <div class="form__field">
                    <label class="form__label" for="phone">Telefonnummer</label>
                    <input
                        class="form__control"
                        id="phone"
                        type="tel"
                        wire:model="phone"
                        autocomplete="tel"
                        @error('phone')
                            aria-invalid="true"
                            aria-describedby="profile-phone-error"
                        @enderror
                    >
                    @error('phone')
                        <p class="form__error" id="profile-phone-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section class="portal-page__section">
            <div class="portal-page__section-header">
                <h2>Adresse</h2>
            </div>

            <div class="form__grid form__grid--2">
                <div class="form__field">
                    <label class="form__label" for="street">Straße</label>
                    <input
                        class="form__control"
                        id="street"
                        type="text"
                        wire:model="street"
                        autocomplete="address-line1"
                        @error('street')
                            aria-invalid="true"
                            aria-describedby="profile-street-error"
                        @enderror
                    >
                    @error('street')
                        <p class="form__error" id="profile-street-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__field">
                    <label class="form__label" for="house_number">Hausnummer</label>
                    <input
                        class="form__control"
                        id="house_number"
                        type="text"
                        wire:model="house_number"
                        @error('house_number')
                            aria-invalid="true"
                            aria-describedby="profile-house-number-error"
                        @enderror
                    >
                    @error('house_number')
                        <p class="form__error" id="profile-house-number-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__field">
                    <label class="form__label" for="postal_code">Postleitzahl</label>
                    <input
                        class="form__control"
                        id="postal_code"
                        type="text"
                        wire:model="postal_code"
                        autocomplete="postal-code"
                        @error('postal_code')
                            aria-invalid="true"
                            aria-describedby="profile-postal-code-error"
                        @enderror
                    >
                    @error('postal_code')
                        <p class="form__error" id="profile-postal-code-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__field">
                    <label class="form__label" for="city">Ort</label>
                    <input
                        class="form__control"
                        id="city"
                        type="text"
                        wire:model="city"
                        autocomplete="address-level2"
                        @error('city')
                            aria-invalid="true"
                            aria-describedby="profile-city-error"
                        @enderror
                    >
                    @error('city')
                        <p class="form__error" id="profile-city-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__field">
                    <label class="form__label" for="country_code">Ländercode</label>
                    <input
                        class="form__control"
                        id="country_code"
                        type="text"
                        maxlength="2"
                        wire:model="country_code"
                        autocomplete="country"
                        required
                        @error('country_code')
                            aria-invalid="true"
                            aria-describedby="profile-country-code-error"
                        @enderror
                    >
                    @error('country_code')
                        <p class="form__error" id="profile-country-code-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <div class="portal-page__related">
            <a href="{{ route('my.security') }}">
                Sicherheit und Zwei-Faktor-Authentifizierung
            </a>

            <a href="{{ route('my.account-deletion') }}">
                Konto löschen
            </a>
        </div>

        <div class="portal-page__actions">
            <button class="btn" type="submit" wire:loading.attr="disabled" wire:target="save">
                Profil speichern
            </button>
        </div>
    </form>
</div>
