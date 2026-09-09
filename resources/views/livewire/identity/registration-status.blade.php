<?php

use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Models\RegistrationRequest;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Modules\Communication\Exceptions\EmailTemplateUnavailable;
use App\Modules\Identity\Actions\Registration\ResendRegistrationVerificationAction;
use App\Modules\Identity\Exceptions\RegistrationVerificationCannotBeResent;

new #[Layout('components.layouts.public')]
    class extends Component {
    public string $publicId;

    public string $recipientEmail = '';

    public bool $mailPrepared = false;

    public bool $resent = false;

    public function mount(
        string $publicId,
    ): void {
        $registrationRequest = RegistrationRequest::query()
            ->where('public_id', $publicId)
            ->where(
                'status',
                RegistrationRequestStatus::PendingVerification,
            )
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $this->publicId =
            $registrationRequest->public_id;

        $this->recipientEmail =
            $registrationRequest
                ->verification_recipient_email;

        $this->mailPrepared =
            $registrationRequest
                ->verification_sent_at !== null;
    }

    public function resend(): void
    {
        $this->resetErrorBag();

        $this->resent = false;

        try {
            app(
                ResendRegistrationVerificationAction::class
            )->execute(
                    publicId: $this->publicId,
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
        } catch (
            RegistrationVerificationCannotBeResent $exception
        ) {
            $this->addError(
                'resend',
                $exception->getMessage(),
            );

            return;
        } catch (EmailTemplateUnavailable) {
            $this->addError(
                'resend',
                'Die Bestätigungs-E-Mail kann derzeit nicht versendet werden.',
            );

            return;
        }

        $this->mailPrepared = true;
        $this->resent = true;
    }
};

?>

<div class="portal-page portal-page--small">
    <header class="portal-page__header">
        <h1>Registrierung bestätigen</h1>
    </header>

    @if ($mailPrepared)
        <x-vdbs.notice type="info" role="status">
            <div class="stack stack--sm">
                <p>
                    Für Ihre Registrierung wurde eine
                    Bestätigungs-E-Mail an
                    <strong>{{ $recipientEmail }}</strong>
                    vorbereitet.
                </p>

                <p>
                    Bitte öffnen Sie den Link in der E-Mail,
                    um die Registrierung abzuschließen.
                </p>
            </div>
        </x-vdbs.notice>
    @else
        <x-vdbs.notice type="warning" role="status">
            <div class="stack stack--sm">
                <p>Ihre Registrierung wurde gespeichert.</p>

                <p>
                    Die Bestätigungs-E-Mail konnte bislang
                    noch nicht vorbereitet werden.
                </p>
            </div>
        </x-vdbs.notice>
    @endif

    @if ($resent)
        <x-vdbs.notice type="success" role="status">
            Eine neue Bestätigungs-E-Mail wurde
            vorbereitet.
        </x-vdbs.notice>
    @endif

    @error('resend')
        <x-vdbs.notice type="danger" role="alert">
            {{ $message }}
        </x-vdbs.notice>
    @enderror

    <form class="portal-page__form form" wire:submit="resend">
        <div class="portal-page__actions">
            <button class="btn" type="submit" wire:loading.attr="disabled" wire:target="resend">
                <span wire:loading.remove wire:target="resend">
                    Bestätigungs-E-Mail erneut senden
                </span>

                <span wire:loading wire:target="resend">
                    E-Mail wird vorbereitet …
                </span>
            </button>
        </div>
    </form>
</div>
