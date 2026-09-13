<?php

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
        public string $first_name = '';

        public string $last_name = '';

        public string $email = '';

        public string $recipient = '';

        public string $subject = '';

        public string $content = '';

        public bool $privacy_accepted = false;

        public bool $sent = false;

        public function sendMessage(): void
        {
            $this->validate([
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email:rfc', 'max:254'],
                'recipient' => ['required', 'in:support,contact'],
                'subject' => ['required', 'string', 'max:180'],
                'content' => ['required', 'string', 'max:5000'],
                'privacy_accepted' => ['accepted'],
            ], attributes: [
                'first_name' => 'Vorname',
                'last_name' => 'Nachname',
                'email' => 'E-Mail-Adresse',
                'recipient' => 'Empfänger',
                'subject' => 'Betreff',
                'content' => 'Inhalt',
                'privacy_accepted' => 'Einwilligung',
            ]);

            $recipient = match ($this->recipient) {
                'support' => 'support@portal.vdb.schule',
                'contact' => 'kontakt@vdb.schule',
            };

            $body = implode("\n", [
                'Kontaktformular VDBS Serviceportal',
                '',
                'Vorname: '.$this->first_name,
                'Nachname: '.$this->last_name,
                'E-Mail-Adresse: '.$this->email,
                '',
                $this->content,
            ]);

            Mail::raw(
                $body,
                function (Message $message) use ($recipient): void {
                    $message
                        ->to($recipient)
                        ->replyTo(
                            $this->email,
                            trim($this->first_name.' '.$this->last_name),
                        )
                        ->subject($this->subject);
                },
            );

            $this->reset([
                'first_name',
                'last_name',
                'email',
                'recipient',
                'subject',
                'content',
                'privacy_accepted',
            ]);
            $this->sent = true;
        }
};

?>

<div class="mockup-page contact-form-page">
    <section class="mockup-panel contact-form-panel" aria-labelledby="contact-form-heading">
        <h1 id="contact-form-heading">Kontaktformular</h1>

        @if ($sent)
            <x-vdbs.notice type="success" role="status">
                Ihre Nachricht wurde versendet. Vielen Dank für Ihre Kontaktaufnahme.
            </x-vdbs.notice>
        @endif

        <form class="mockup-form contact-form-panel__form" wire:submit="sendMessage">
            <div class="mockup-form__grid">
                <div class="form__field">
                    <label class="form__label" for="contact_first_name">Vorname</label>
                    <input class="form__control" id="contact_first_name" type="text" wire:model="first_name" autocomplete="given-name" placeholder="Max" required>
                    @error('first_name') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="form__field">
                    <label class="form__label" for="contact_last_name">Nachname</label>
                    <input class="form__control" id="contact_last_name" type="text" wire:model="last_name" autocomplete="family-name" placeholder="Mustermann" required>
                    @error('last_name') <p class="form__error" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form__field">
                <label class="form__label" for="contact_email">E-Mail-Adresse</label>
                <input class="form__control" id="contact_email" type="email" wire:model="email" autocomplete="email" placeholder="max.mustermann@beispiel.xyz" required>
                @error('email') <p class="form__error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="form__field">
                <label class="form__label" for="contact_recipient">Empfänger</label>
                <select class="form__control" id="contact_recipient" wire:model="recipient" required>
                    <option value="">-- Bitte eine Option wählen --</option>
                    <option value="support">Support</option>
                    <option value="contact">Kontakt</option>
                </select>
                @error('recipient') <p class="form__error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="form__field">
                <label class="form__label" for="contact_subject">Betreff</label>
                <input class="form__control" id="contact_subject" type="text" wire:model="subject" placeholder="Geben Sie Ihrem Anliegen einen Betreff" required>
                @error('subject') <p class="form__error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="form__field">
                <label class="form__label" for="contact_content">Inhalt</label>
                <textarea class="form__control mockup-form__textarea" id="contact_content" wire:model="content" rows="6" placeholder="Ihre Nachricht..." required></textarea>
                @error('content') <p class="form__error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="form__choice contact-form-panel__consent">
                <input id="contact_privacy_accepted" type="checkbox" wire:model="privacy_accepted" required>
                <label for="contact_privacy_accepted">
                    Ich willige ein, dass meine Daten zum Zweck der Kontaktaufnahme gespeichert und verarbeitet werden.
                    Ich kann diese Einwilligung jederzeit widerrufen. Weitere Informationen finden Sie in unserer
                    <a href="{{ route('portal.privacy') }}">Datenschutzerklärung</a>.
                </label>
            </div>
            @error('privacy_accepted') <p class="form__error" role="alert">{{ $message }}</p> @enderror

            <div class="mockup-form__actions">
                <button class="btn" type="submit" wire:loading.attr="disabled" wire:target="sendMessage">
                    <x-vdbs.icon name="mail" size="18" />
                    <span wire:loading.remove wire:target="sendMessage">Absenden</span>
                    <span wire:loading wire:target="sendMessage">Wird gesendet …</span>
                </button>
            </div>
        </form>
    </section>
</div>
