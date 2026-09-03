<?php

use App\Modules\Identity\Actions\Registration\StartRegistrationWorkflowAction;
use App\Modules\Identity\Exceptions\RegistrationCannotStart;
use App\Modules\Identity\Exceptions\RegistrationVerificationEmailUnavailable;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public string $first_name = '';

    public string $last_name = '';

    public string $birth_date = '';

    public string $email = '';

    public string $password = '';

    public bool $privacy_accepted = false;

    public function register(): void
    {
        $this->resetErrorBag();

        try {
            $registrationRequest = app(
                StartRegistrationWorkflowAction::class
            )->execute(
                    firstName: $this->first_name,
                    lastName: $this->last_name,
                    birthDate: $this->birth_date,
                    email: $this->email,
                    password: $this->password,
                    privacyAccepted: $this->privacy_accepted,
                    privacyNoticeVersion: config(
                        'privacy.registration_notice_version'
                    ),
                    consentedAt: now(),
                );
        } catch (RegistrationCannotStart $exception) {
            /*
             * Bewusst nur die neutrale fachliche Meldung.
             * Es wird nicht offengelegt, welcher Datensatz
             * oder welche E-Mail bereits existiert.
             */
            $this->addError(
                'registration',
                $exception->getMessage(),
            );

            return;
        } catch (
            RegistrationVerificationEmailUnavailable $exception
        ) {
            /*
             * Der RegistrationRequest wurde bereits
             * gespeichert. Auch ohne initial vorbereitete
             * E-Mail geht es deshalb auf die persistente
             * Statusseite.
             */
            $this->password = '';

            $this->redirectRoute(
                'my.registration.status',
                [
                    'publicId' =>
                        $exception->registrationPublicId,
                ],
            );

            return;
        }

        $this->password = '';

        $this->redirectRoute(
            'my.registration.status',
            [
                'publicId' =>
                    $registrationRequest->public_id,
            ],
        );
    }
};

?>

<div class="card">
    <h1>Registrieren</h1>

    <p>
        Erstellen Sie ein Benutzerkonto für das
        VDB-Portal. Nach der Registrierung erhalten
        Sie eine Bestätigungs-E-Mail.
    </p>

    @error('registration')
        <p role="alert">
            {{ $message }}
        </p>
    @enderror

    <form wire:submit="register">
        <div class="field">
            <label for="first_name">
                Vorname
            </label>

            <input id="first_name" name="first_name" type="text" wire:model="first_name" autocomplete="given-name"
                required>

            @error('first_name')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="field">
            <label for="last_name">
                Nachname
            </label>

            <input id="last_name" name="last_name" type="text" wire:model="last_name" autocomplete="family-name"
                required>

            @error('last_name')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="field">
            <label for="birth_date">
                Geburtsdatum
            </label>

            <input id="birth_date" name="birth_date" type="date" wire:model="birth_date" autocomplete="bday" required>

            @error('birth_date')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="field">
            <label for="email">
                E-Mail-Adresse
            </label>

            <input id="email" name="email" type="email" wire:model="email" autocomplete="email" required>

            @error('email')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="field">
            <label for="password">
                Passwort
            </label>

            <input id="password" name="password" type="password" wire:model="password" autocomplete="new-password"
                required>

            <small>
                Mindestens 10 Zeichen sowie Groß- und
                Kleinbuchstaben, Zahl und Sonderzeichen.
            </small>

            @error('password')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="field">
            <label>
                <input name="privacy_accepted" type="checkbox" wire:model="privacy_accepted">

                Ich stimme der Verarbeitung meiner Daten
                gemäß Datenschutzhinweis zu.
            </label>

            @error('privacy_accepted')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="register">
            <span wire:loading.remove wire:target="register">
                Registrieren
            </span>

            <span wire:loading wire:target="register">
                Registrierung wird verarbeitet …
            </span>
        </button>
    </form>
</div>