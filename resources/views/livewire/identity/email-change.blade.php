<?php

use App\Modules\Identity\Actions\EmailChange\StartEmailChangeWorkflowAction;
use App\Modules\Identity\Enums\EmailChangeRequestStatus;
use App\Modules\Identity\Exceptions\EmailChangeCannotStart;
use App\Modules\Identity\Models\EmailChangeRequest;
use App\Modules\Identity\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public string $currentEmail = '';

    public string $newEmail = '';

    public ?string $pendingEmail = null;

    public ?string $pendingExpiresAt = null;

    public bool $verificationPrepared = false;

    public bool $requested = false;

    public function mount(): void
    {
        $this->refreshState();
    }

    public function requestChange(
        StartEmailChangeWorkflowAction $startEmailChange,
    ): void {
        $user = auth()->user();

        if (!$user instanceof User) {
            return;
        }

        $this->resetErrorBag();
        $this->requested = false;

        $this->validate([
            'newEmail' => [
                'required',
                'string',
                'email',
                'max:254',
            ],
        ]);

        try {
            $startEmailChange->execute(
                user: $user,
                newEmail: $this->newEmail,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            );
        } catch (EmailChangeCannotStart $exception) {
            $this->addError(
                'newEmail',
                $exception->getMessage(),
            );

            return;
        }

        $this->newEmail = '';
        $this->requested = true;

        $this->refreshState();
    }

    private function refreshState(): void
    {
        $user = auth()->user();

        if (!$user instanceof User) {
            return;
        }

        $this->currentEmail = $user
            ->person()
            ->value('email')
            ?? $user->email;

        $pending = EmailChangeRequest::query()
            ->where('user_id', $user->id)
            ->where(
                'status',
                EmailChangeRequestStatus::Pending,
            )
            ->where(
                'expires_at',
                '>',
                now(),
            )
            ->latest('id')
            ->first();

        if ($pending === null) {
            $this->pendingEmail = null;
            $this->pendingExpiresAt = null;
            $this->verificationPrepared = false;

            return;
        }

        $this->pendingEmail =
            $pending->new_email;

        $this->pendingExpiresAt =
            $pending->expires_at
                ->format('d.m.Y H:i');

        $this->verificationPrepared =
            $pending->verification_sent_at
            !== null;
    }
};

?>

<div class="card">
    <h1>E-Mail-Adresse ändern</h1>

    <p>
        Aktuelle E-Mail-Adresse:
        <strong>{{ $currentEmail }}</strong>
    </p>

    @if ($pendingEmail !== null)
        <div role="status">
            <p>
                Offene Änderung auf
                <strong>{{ $pendingEmail }}</strong>.
            </p>

            <p>
                Bestätigung möglich bis
                {{ $pendingExpiresAt }}.
            </p>

            @if ($verificationPrepared)
                <p>
                    Die Bestätigungs-E-Mail wurde
                    zur Versandwarteschlange hinzugefügt.
                </p>
            @else
                <p>
                    Die Bestätigungs-E-Mail konnte noch
                    nicht vorbereitet werden. Die Änderung
                    wurde nicht verworfen.
                </p>
            @endif
        </div>
    @endif

    @if ($requested)
        <p role="status">
            Der Änderungsprozess wurde angelegt.
            Ihre bisherige E-Mail-Adresse bleibt bis
            zur Bestätigung unverändert aktiv.
        </p>
    @endif

    <form wire:submit="requestChange">
        <div class="field">
            <label for="newEmail">
                Neue E-Mail-Adresse
            </label>

            <input id="newEmail" type="email" wire:model="newEmail" autocomplete="email" required>

            @error('newEmail')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="requestChange">
            Bestätigung anfordern
        </button>
    </form>
</div>