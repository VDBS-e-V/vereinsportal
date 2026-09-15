<?php

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.public')]
    class extends Component {
        use WithFileUploads;

        public int $step = 1;

        public string $connection = 'Teamer:in';

        public string $organisation = '';

        public string $details = '';

        /** @var array<int, TemporaryUploadedFile> */
        public array $proofs = [];

        public string $first_name = '';

        public string $last_name = '';

        public string $email = '';

        public string $birth_date = '';

        public string $phone = '';

        public string $gender = '';

        public string $preferred_username = '';

        public bool $terms_accepted = false;

        public bool $submitted = false;

        public function back(): void
        {
            if ($this->step > 1) {
                $this->step--;

                return;
            }

            $this->redirectRoute('my.home');
        }

        public function continueToContact(): void
        {
            $this->validateConnection();
            $this->step = 2;
        }

        public function review(): void
        {
            $this->validateConnection();
            $this->validateContact();
            $this->step = 3;
        }

        public function submitApplication(): void
        {
            $this->validateConnection();
            $this->validateContact();

            $this->validate([
                'terms_accepted' => ['accepted'],
            ], attributes: [
                'terms_accepted' => 'Nutzungsbedingungen und Datenschutzerklärung',
            ]);

            $body = implode("\n", [
                'Antrag - Zugang zum Portal',
                '',
                'Vorname: '.$this->first_name,
                'Nachname: '.$this->last_name,
                'EMail: '.$this->email,
                'Geburtsdatum: '.$this->birth_date,
                'Telefon: '.$this->phone,
                'Geschlecht: '.($this->gender !== '' ? $this->gender : 'Keine Angabe'),
                'Verbindung: '.$this->connection,
                'Traeger: '.$this->organisation,
                'Wunschname: '.$this->preferred_username,
                'details: '.$this->details,
            ]);

            Mail::raw(
                $body,
                function (Message $message): void {
                    $message
                        ->to('support@portal.vdb.schule')
                        ->replyTo(
                            $this->email,
                            trim($this->first_name.' '.$this->last_name),
                        )
                        ->subject('Antrag - Zugang zum Portal');

                    foreach ($this->proofs as $proof) {
                        $message->attach(
                            $proof->getPathname(),
                            [
                                'as' => $proof->getClientOriginalName(),
                                'mime' => $proof->getMimeType(),
                            ],
                        );
                    }
                },
            );

            $this->submitted = true;
        }

        private function validateConnection(): void
        {
            $this->validate([
                'connection' => [
                    'required',
                    'in:Teamer:in,Vereinsmitglied:in,Verwaltung,Schüler:in,Lehrkraft,Partner:in,Sonstiges',
                ],
                'organisation' => ['nullable', 'string', 'max:255'],
                'details' => ['nullable', 'string', 'max:2000'],
                'proofs' => ['array', 'max:5'],
                'proofs.*' => [
                    'file',
                    'mimes:pdf,jpg,jpeg,png,webp',
                    'max:5120',
                ],
            ], attributes: [
                'connection' => 'Verbindung zum Verein',
                'organisation' => 'Träger / Schule',
                'details' => 'Weitere Details',
                'proofs' => 'Nachweise',
                'proofs.*' => 'Nachweis',
            ]);
        }

        private function validateContact(): void
        {
            $this->validate([
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email:rfc', 'max:254'],
                'birth_date' => ['required', 'date', 'before:today'],
                'phone' => ['nullable', 'string', 'max:50'],
                'gender' => [
                    'nullable',
                    'in:weiblich,männlich,divers,Keine Angabe',
                ],
                'preferred_username' => ['nullable', 'string', 'max:100'],
            ], attributes: [
                'first_name' => 'Vorname',
                'last_name' => 'Nachname',
                'email' => 'E-Mail-Adresse',
                'birth_date' => 'Geburtsdatum',
                'phone' => 'Telefon',
                'gender' => 'Geschlecht',
                'preferred_username' => 'Wunsch Nutzername',
            ]);
        }
};

?>

<div class="mockup-page access-application">
    <h1>Antrag - Zugang zum Portal</h1>

    <section class="mockup-panel" aria-labelledby="access-application-step-heading">
        @if ($submitted)
            <div class="access-application__success" role="status">
                <x-vdbs.icon name="check" size="30" />
                <div>
                    <h2>Antrag abgesendet</h2>
                    <p>
                        Vielen Dank. Ihr Antrag wurde an den Support des VDBS Serviceportals übermittelt.
                    </p>
                </div>
            </div>
        @else
            <nav class="application-stepper" aria-label="Antragsschritte">
                <span>0. Einwilligung</span>
                <span aria-hidden="true">›</span>
                <span @class(['application-stepper__active' => $step === 1])>1. Verbindung</span>
                <span aria-hidden="true">›</span>
                <span @class(['application-stepper__active' => $step === 2])>2. Kontaktdaten</span>
                <span aria-hidden="true">›</span>
                <span @class(['application-stepper__active' => $step === 3])>3. ToS &amp; Prüfen</span>
            </nav>

            <h2 id="access-application-step-heading" class="vdbs-visually-hidden">
                @if ($step === 1)
                    Verbindung
                @elseif ($step === 2)
                    Kontaktdaten
                @else
                    ToS und Angaben prüfen
                @endif
            </h2>

            @if ($step === 1)
                <form class="mockup-form access-application__form" wire:submit="continueToContact">
                    <div class="form__field">
                        <label class="form__label" for="connection">Verbindung zum Verein <span aria-hidden="true">*</span></label>
                        <select class="form__control" id="connection" wire:model="connection" required>
                            <option>Teamer:in</option>
                            <option>Vereinsmitglied:in</option>
                            <option>Verwaltung</option>
                            <option>Schüler:in</option>
                            <option>Lehrkraft</option>
                            <option>Partner:in</option>
                            <option>Sonstiges</option>
                        </select>
                        @error('connection') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="organisation">Träger / Schule (optional)</label>
                        <input class="form__control" id="organisation" type="text" wire:model="organisation" placeholder="Träger oder Schule">
                        @error('organisation') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="details">Weitere Details (optional)</label>
                        <textarea class="form__control mockup-form__textarea" id="details" wire:model="details" rows="5" placeholder="z.B. Rolle, Verein, Zugehörigkeit"></textarea>
                        @error('details') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="proofs">Nachweise (PDF oder Bilder, max. 5 MB pro Datei)</label>
                        <input class="form__control mockup-form__file" id="proofs" type="file" wire:model="proofs" multiple accept=".pdf,.jpg,.jpeg,.png,.webp">
                        @error('proofs') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                        @error('proofs.*') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="mockup-form__actions">
                        <button class="btn" type="button" wire:click="back">
                            <x-vdbs.icon name="arrow-left" size="20" />
                            <span>Zurück</span>
                        </button>
                        <button class="btn" type="submit">
                            <x-vdbs.icon name="arrow-right" size="20" />
                            <span>Weiter</span>
                        </button>
                    </div>
                </form>
            @elseif ($step === 2)
                <form class="mockup-form access-application__form" wire:submit="review">
                    <div class="form__field">
                        <label class="form__label" for="first_name">Vorname <span aria-hidden="true">*</span></label>
                        <input class="form__control" id="first_name" type="text" wire:model="first_name" autocomplete="given-name" required>
                        @error('first_name') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="last_name">Nachname <span aria-hidden="true">*</span></label>
                        <input class="form__control" id="last_name" type="text" wire:model="last_name" autocomplete="family-name" required>
                        @error('last_name') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="email">E-Mail-Adresse <span aria-hidden="true">*</span></label>
                        <input class="form__control" id="email" type="email" wire:model="email" autocomplete="email" required>
                        @error('email') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="birth_date">Geburtsdatum <span aria-hidden="true">*</span></label>
                        <input class="form__control" id="birth_date" type="date" wire:model="birth_date" autocomplete="bday" required>
                        @error('birth_date') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="phone">Telefon (optional)</label>
                        <input class="form__control" id="phone" type="tel" wire:model="phone" autocomplete="tel" placeholder="Telefonnummer">
                        @error('phone') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="gender">Geschlecht (optional)</label>
                        <select class="form__control" id="gender" wire:model="gender">
                            <option value="">Keine Angabe</option>
                            <option value="weiblich">weiblich</option>
                            <option value="männlich">männlich</option>
                            <option value="divers">divers</option>
                        </select>
                        @error('gender') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="preferred_username">Wunsch Nutzername (optional)</label>
                        <input class="form__control" id="preferred_username" type="text" wire:model="preferred_username" placeholder="gewünschter Benutzername">
                        @error('preferred_username') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="mockup-form__actions">
                        <button class="btn" type="button" wire:click="back">
                            <x-vdbs.icon name="arrow-left" size="20" />
                            <span>Zurück</span>
                        </button>
                        <button class="btn" type="submit">
                            <x-vdbs.icon name="check" size="20" />
                            <span>Daten prüfen</span>
                        </button>
                    </div>
                </form>
            @else
                <form class="mockup-form access-application__review" wire:submit="submitApplication">
                    <div class="form__choice access-application__terms">
                        <input id="terms_accepted" type="checkbox" wire:model="terms_accepted" required>
                        <label for="terms_accepted">
                            Ich akzeptiere die <a href="{{ route('portal.privacy') }}">Nutzungsbedingungen</a><br>
                            und die <a href="{{ route('portal.privacy') }}">Datenschutzerklärung</a>. <span aria-hidden="true">*</span>
                        </label>
                    </div>
                    @error('terms_accepted') <p class="form__error" role="alert">{{ $message }}</p> @enderror

                    <h2>Prüfen Sie ihre Angaben</h2>

                    <div class="access-application__summary">
                        <dl>
                            <div><dt>Vorname:</dt><dd>{{ $first_name }}</dd></div>
                            <div><dt>Nachname:</dt><dd>{{ $last_name }}</dd></div>
                            <div><dt>EMail:</dt><dd>{{ $email }}</dd></div>
                            <div><dt>Geburtsdatum:</dt><dd>{{ $birth_date }}</dd></div>
                            <div><dt>Telefon:</dt><dd>{{ $phone }}</dd></div>
                            <div><dt>Verbindung:</dt><dd>{{ $connection }}</dd></div>
                            <div><dt>Traeger:</dt><dd>{{ $organisation }}</dd></div>
                            <div><dt>Wunschname:</dt><dd>{{ $preferred_username }}</dd></div>
                            <div><dt>details:</dt><dd>{{ $details }}</dd></div>
                        </dl>
                    </div>

                    <div class="mockup-form__actions">
                        <button class="btn" type="button" wire:click="back">
                            <x-vdbs.icon name="arrow-left" size="20" />
                            <span>Zurück</span>
                        </button>
                        <button class="btn" type="submit" wire:loading.attr="disabled" wire:target="submitApplication">
                            <x-vdbs.icon name="check" size="20" />
                            <span wire:loading.remove wire:target="submitApplication">Antrag absenden</span>
                            <span wire:loading wire:target="submitApplication">Antrag wird gesendet …</span>
                        </button>
                    </div>
                </form>
            @endif
        @endif
    </section>
</div>
