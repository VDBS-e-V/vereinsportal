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

<div class="portal-page portal-page--small">
    <header class="portal-page__header">
        <h1>Kontolöschung</h1>

        <p class="portal-page__lead">
            Hier können Sie die Löschung Ihres Kontos
            anfordern. Die Löschung wird erst nach einer
            Bestätigung über einen signierten Link fortgesetzt.
        </p>
    </header>

    @if ($errorMessage !== null)
        <x-vdbs.notice type="danger" role="alert">
            {{ $errorMessage }}
        </x-vdbs.notice>
    @endif

    @if ($requested)
        <x-vdbs.notice type="success" role="status">
            Der Löschantrag wurde gespeichert.
        </x-vdbs.notice>
    @endif

    @if ($hasOpenRequest)
        <section class="portal-page__section">
            <div class="portal-page__section-header">
                <h2>Offener Löschantrag</h2>
            </div>

            <dl class="metadata-list">
                <div>
                    <dt>Status</dt>
                    <dd>
                        @if (
                            $requestStatus
                            === \App\Modules\Identity\Enums\AccountDeletionRequestStatus::PendingDeletion->value
                        )
                            <x-vdbs.status type="warning">Löschung vorgemerkt</x-vdbs.status>
                        @else
                            <x-vdbs.status type="info">Bestätigung ausstehend</x-vdbs.status>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt>Angefordert am</dt>
                    <dd>{{ $requestedAt }}</dd>
                </div>

                @if (
                    $requestStatus
                    === \App\Modules\Identity\Enums\AccountDeletionRequestStatus::PendingConfirmation->value
                    && $confirmationPrepared
                )
                    <div>
                        <dt>Vorbereitet am</dt>
                        <dd>{{ $confirmationSentAt }}</dd>
                    </div>
                @endif

                @if (
                    $requestStatus
                    === \App\Modules\Identity\Enums\AccountDeletionRequestStatus::PendingDeletion->value
                    && $revokeUntil !== null
                )
                    <div>
                        <dt>Widerruf möglich bis</dt>
                        <dd>{{ $revokeUntil }}</dd>
                    </div>
                @endif
            </dl>

            @if (
                $requestStatus
                === \App\Modules\Identity\Enums\AccountDeletionRequestStatus::PendingConfirmation->value
            )
                @if ($confirmationPrepared)
                    <x-vdbs.notice type="info" role="status">
                        Die Bestätigungs-E-Mail wurde
                        zur Versandwarteschlange hinzugefügt.
                    </x-vdbs.notice>
                @else
                    <x-vdbs.notice type="warning" role="alert">
                        Der Löschantrag ist gespeichert,
                        aber die Bestätigungs-E-Mail konnte
                        noch nicht vorbereitet werden.
                    </x-vdbs.notice>
                @endif
            @elseif (
                $requestStatus
                === \App\Modules\Identity\Enums\AccountDeletionRequestStatus::PendingDeletion->value
            )
                <x-vdbs.notice type="success" role="status">
                    Die Kontolöschung wurde bestätigt.
                </x-vdbs.notice>
            @endif
        </section>
    @else
        <x-vdbs.danger-zone
            title="Kontolöschung anfordern"
            description="Nach dem Absenden erhalten Sie eine Bestätigungs-E-Mail. Ohne Bestätigung wird die Löschung nicht fortgesetzt."
        >
            <p>
                Prüfen Sie vor dem Absenden, ob Sie noch Daten oder
                Dokumente aus Ihrem Konto benötigen.
            </p>

            <x-slot:actions>
                <button
                    class="btn btn--danger"
                    type="button"
                    wire:click="requestDeletion"
                    wire:loading.attr="disabled"
                    wire:target="requestDeletion"
                    wire:confirm="Kontolöschung wirklich anfordern?"
                >
                    Kontolöschung anfordern
                </button>
            </x-slot:actions>
        </x-vdbs.danger-zone>
    @endif

    <div class="portal-page__links">
        <a href="{{ route('my.profile') }}">
            Zurück zum Profil
        </a>
    </div>
</div>
