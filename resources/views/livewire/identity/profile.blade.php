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

<div class="card">
    <h1>Profil</h1>

    @if ($saved)
        <p role="status">
            Ihre Profildaten wurden gespeichert.
        </p>
    @endif

    <form wire:submit="save">
        <div class="field">
            <label for="title">Titel</label>
            <input id="title" type="text" wire:model="title">
            @error('title')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="first_name">Vorname</label>
            <input id="first_name" type="text" wire:model="first_name" required>
            @error('first_name')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="name_addition">
                Namenszusatz
            </label>
            <input id="name_addition" type="text" wire:model="name_addition">
            @error('name_addition')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="last_name">
                Nachname
            </label>
            <input id="last_name" type="text" wire:model="last_name" required>
            @error('last_name')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="birth_date">
                Geburtsdatum
            </label>
            <input id="birth_date" type="date" wire:model="birth_date" required>
            @error('birth_date')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="email">
                E-Mail-Adresse
            </label>
            <input id="email" type="email" wire:model="email" readonly>

            <p>
                <a href="{{ route('my.email-change') }}">
                    E-Mail-Adresse ändern
                </a>
            </p>
        </div>

        <div class="field">
            <label for="phone">
                Telefonnummer
            </label>
            <input id="phone" type="text" wire:model="phone">
            @error('phone')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="street">Straße</label>
            <input id="street" type="text" wire:model="street">
            @error('street')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="house_number">
                Hausnummer
            </label>
            <input id="house_number" type="text" wire:model="house_number">
            @error('house_number')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="postal_code">
                Postleitzahl
            </label>
            <input id="postal_code" type="text" wire:model="postal_code">
            @error('postal_code')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="city">Ort</label>
            <input id="city" type="text" wire:model="city">
            @error('city')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="country_code">
                Ländercode
            </label>
            <input id="country_code" type="text" maxlength="2" wire:model="country_code" required>
            @error('country_code')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <p>
            <a href="{{ route('my.security') }}">
                Sicherheit und Zwei-Faktor-Authentifizierung
            </a>
        </p>

        <button type="submit" wire:loading.attr="disabled" wire:target="save">
            Profil speichern
        </button>
    </form>
</div>