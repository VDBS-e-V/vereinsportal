<?php

use App\Modules\Identity\Actions\Auth\LogoutAction;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public function logout(
        LogoutAction $logout,
    ): void {
        $user = auth()->user();

        if ($user !== null) {
            $logout->execute(
                user: $user,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            );
        }

        $this->redirectRoute(
            'my.login',
            navigate: false,
        );
    }
};

?>

<div class="card">
    <h1>Mein Portal</h1>

    <p>
        Sie sind angemeldet.
    </p>

    <button type="button" wire:click="logout">
        Abmelden
    </button>
</div>