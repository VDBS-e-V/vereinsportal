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

<div class="portal-page portal-page--small">
    <header class="portal-page__header">
        <h1>Registrieren</h1>

        <p class="portal-page__lead">
            Erstellen Sie ein Benutzerkonto für das
            VDB-Portal. Nach der Registrierung erhalten
            Sie eine Bestätigungs-E-Mail.
        </p>
    </header>

    @error('registration')
        <x-vdbs.notice type="danger" role="alert">
            {{ $message }}
        </x-vdbs.notice>
    @enderror

    <form class="portal-page__form form" wire:submit="register">
        <div class="form__grid form__grid--2">
            <div class="form__field">
                <label class="form__label" for="first_name">Vorname</label>

                <input
                    class="form__control"
                    id="first_name"
                    name="first_name"
                    type="text"
                    wire:model="first_name"
                    autocomplete="given-name"
                    required
                    @error('first_name')
                        aria-invalid="true"
                        aria-describedby="registration-first-name-error"
                    @enderror
                >

                @error('first_name')
                    <p class="form__error" id="registration-first-name-error" role="alert">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="form__field">
                <label class="form__label" for="last_name">Nachname</label>

                <input
                    class="form__control"
                    id="last_name"
                    name="last_name"
                    type="text"
                    wire:model="last_name"
                    autocomplete="family-name"
                    required
                    @error('last_name')
                        aria-invalid="true"
                        aria-describedby="registration-last-name-error"
                    @enderror
                >

                @error('last_name')
                    <p class="form__error" id="registration-last-name-error" role="alert">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="form__field">
                <label class="form__label" for="birth_date">Geburtsdatum</label>

                <input
                    class="form__control"
                    id="birth_date"
                    name="birth_date"
                    type="date"
                    wire:model="birth_date"
                    autocomplete="bday"
                    required
                    @error('birth_date')
                        aria-invalid="true"
                        aria-describedby="registration-birth-date-error"
                    @enderror
                >

                @error('birth_date')
                    <p class="form__error" id="registration-birth-date-error" role="alert">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="form__field">
                <label class="form__label" for="email">E-Mail-Adresse</label>

                <input
                    class="form__control"
                    id="email"
                    name="email"
                    type="email"
                    wire:model="email"
                    autocomplete="email"
                    required
                    @error('email')
                        aria-invalid="true"
                        aria-describedby="registration-email-error"
                    @enderror
                >

                @error('email')
                    <p class="form__error" id="registration-email-error" role="alert">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

        <div class="form__field">
            <label class="form__label" for="password">Passwort</label>

            <input
                class="form__control"
                id="password"
                name="password"
                type="password"
                wire:model="password"
                autocomplete="new-password"
                required
                aria-describedby="registration-password-help @error('password') registration-password-error @enderror"
                @error('password')
                    aria-invalid="true"
                @enderror
            >

            <p class="form__help" id="registration-password-help">
                Mindestens 10 Zeichen sowie Groß- und
                Kleinbuchstaben, Zahl und Sonderzeichen.
            </p>

            @error('password')
                <p class="form__error" id="registration-password-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <fieldset class="form__fieldset">
            <legend class="form__legend">Datenschutz</legend>

            <div class="form__choice">
                <input
                    id="privacy_accepted"
                    name="privacy_accepted"
                    type="checkbox"
                    wire:model="privacy_accepted"
                    required
                    @error('privacy_accepted')
                        aria-invalid="true"
                        aria-describedby="registration-privacy-error"
                    @enderror
                >

                <label for="privacy_accepted">
                    Ich stimme der Verarbeitung meiner Daten
                    gemäß Datenschutzhinweis zu.
                </label>
            </div>

            @error('privacy_accepted')
                <p class="form__error" id="registration-privacy-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </fieldset>

        <div class="portal-page__actions">
            <button class="btn" type="submit" wire:loading.attr="disabled" wire:target="register">
                <span wire:loading.remove wire:target="register">
                    Registrieren
                </span>

                <span wire:loading wire:target="register">
                    Registrierung wird verarbeitet …
                </span>
            </button>
        </div>
    </form>

    <div class="portal-page__links">
        <a href="{{ route('my.login') }}">
            Bereits registriert? Anmelden
        </a>
    </div>
</div>
