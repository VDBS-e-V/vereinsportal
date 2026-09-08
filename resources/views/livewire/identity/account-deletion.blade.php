<?php

use App\Modules\Identity\Actions\AccountDeletion\StartAccountDeletionWorkflowAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Exceptions\AccountDeletionCannotStart;
use App\Modules\Identity\Exceptions\AccountDeletionConfirmationEmailUnavailable;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public bool $hasOpenRequest = false;

    public ?string $requestStatus = null;

    public ?string $requestedAt = null;

    public ?string $confirmationSentAt = null;

    public ?string $revokeUntil = null;

    public bool $confirmationPrepared = false;

    public bool $requested = false;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->refreshState();
    }

    public function requestDeletion(
        StartAccountDeletionWorkflowAction $startDeletion,
    ): void {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->requested = false;
        $this->errorMessage = null;
        $this->resetErrorBag();

        try {
            $startDeletion->execute(
                user: $user,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            );
        } catch (AccountDeletionCannotStart $exception) {
            $this->errorMessage =
                $exception->getMessage();

            $this->refreshState();

            return;
        } catch (
            AccountDeletionConfirmationEmailUnavailable $exception
        ) {
            /*
             * Der Löschantrag wurde bereits gespeichert.
             * Die Seite zeigt den persistenten Zustand,
             * auch wenn die E-Mail noch nicht vorbereitet
             * werden konnte.
             */
            $this->requested = true;
            $this->refreshState();

            return;
        }

        $this->requested = true;
        $this->refreshState();
    }

    private function refreshState(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $request = AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->whereIn(
                'status',
                [
                    AccountDeletionRequestStatus::PendingConfirmation->value,
                    AccountDeletionRequestStatus::PendingDeletion->value,
                ],
            )
            ->latest('id')
            ->first();

        if ($request === null) {
            $this->hasOpenRequest = false;
            $this->requestStatus = null;
            $this->requestedAt = null;
            $this->confirmationSentAt = null;
            $this->revokeUntil = null;
            $this->confirmationPrepared = false;

            return;
        }

        $this->hasOpenRequest = true;
        $this->requestStatus =
            $request->status->value;

        $this->requestedAt =
            $request->requested_at
                ->format('d.m.Y H:i');

        $this->confirmationSentAt =
            $request->confirmation_sent_at
                ?->format('d.m.Y H:i');

        $this->revokeUntil =
            $request->revoke_until
                ?->format('d.m.Y H:i');

        $this->confirmationPrepared =
            $request->confirmation_sent_at
            !== null;
    }
};

?>

<div class="card">
    <h1>Kontolöschung</h1>

    <p>
        Hier können Sie die Löschung Ihres Kontos
        anfordern. Die Löschung wird erst nach einer
        Bestätigung über einen signierten Link fortgesetzt.
    </p>

    @if ($errorMessage !== null)
        <p role="alert">
            {{ $errorMessage }}
        </p>
    @endif

    @if ($requested)
        <p role="status">
            Der Löschantrag wurde gespeichert.
        </p>
    @endif

    @if ($hasOpenRequest)
        <section>
            <h2>Offener Löschantrag</h2>

            <p>
                Angefordert am:
                <strong>{{ $requestedAt }}</strong>
            </p>

            @if (
                $requestStatus
                === \App\Modules\Identity\Enums\AccountDeletionRequestStatus::PendingConfirmation->value
            )
                @if ($confirmationPrepared)
                    <p role="status">
                        Die Bestätigungs-E-Mail wurde
                        zur Versandwarteschlange hinzugefügt.
                    </p>

                    <p>
                        Vorbereitet am:
                        <strong>{{ $confirmationSentAt }}</strong>
                    </p>
                @else
                    <p role="alert">
                        Der Löschantrag ist gespeichert,
                        aber die Bestätigungs-E-Mail konnte
                        noch nicht vorbereitet werden.
                    </p>
                @endif
            @elseif (
                $requestStatus
                === \App\Modules\Identity\Enums\AccountDeletionRequestStatus::PendingDeletion->value
            )
                <p role="status">
                    Die Kontolöschung wurde bestätigt.
                </p>

                @if ($revokeUntil !== null)
                    <p>
                        Widerruf möglich bis:
                        <strong>{{ $revokeUntil }}</strong>
                    </p>
                @endif
            @endif
        </section>
    @else
        <section>
            <h2>Kontolöschung anfordern</h2>

            <p>
                Nach dem Absenden erhalten Sie eine
                Bestätigungs-E-Mail. Ohne Bestätigung
                wird die Löschung nicht fortgesetzt.
            </p>

            <button
                type="button"
                wire:click="requestDeletion"
                wire:loading.attr="disabled"
                wire:target="requestDeletion"
                wire:confirm="Kontolöschung wirklich anfordern?"
            >
                Kontolöschung anfordern
            </button>
        </section>
    @endif

    <p>
        <a href="{{ route('my.profile') }}">
            Zurück zum Profil
        </a>
    </p>
</div>
