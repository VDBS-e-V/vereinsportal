<?php

use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\AccountAccess;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    /** @var list<array{starts_on: string, ends_on: string, status: string, status_type: string}> */
    public array $membershipRows = [];

    public function mount(AccountAccess $accountAccess): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && $accountAccess->hasActiveRole($user, RoleKey::Member),
            403,
        );

        $person = $user->person;

        abort_unless($person !== null, 404);

        $this->membershipRows = Membership::query()
            ->where('person_id', $person->id)
            ->orderByDesc('starts_on')
            ->get()
            ->map(function (Membership $membership): array {
                $status = $membership->status();

                return [
                    'starts_on' => $membership->starts_on->format('d.m.Y'),
                    'ends_on' => $membership->ends_on?->format('d.m.Y') ?? 'offen',
                    'status' => match ($status) {
                        MembershipStatus::Planned => 'Geplant',
                        MembershipStatus::Active => 'Aktiv',
                        MembershipStatus::Ended => 'Beendet',
                    },
                    'status_type' => match ($status) {
                        MembershipStatus::Planned => 'info',
                        MembershipStatus::Active => 'success',
                        MembershipStatus::Ended => 'neutral',
                    },
                ];
            })
            ->values()
            ->all();
    }
};

?>

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Konto</p>
        <h1>Mitgliedschaft</h1>
        <p class="portal-page__lead">
            Hier sehen Sie den Status und Verlauf Ihrer persönlichen Vereinsmitgliedschaft.
        </p>
    </header>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Mitgliedschaftsverlauf</h2>
        </div>

        @if ($membershipRows === [])
            <x-vdbs.empty-state
                title="Keine Mitgliedschaftsdaten vorhanden"
                description="Für Ihr Konto wurde aktuell kein Mitgliedschaftszeitraum gefunden."
            />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Beginn</th>
                            <th scope="col">Ende</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($membershipRows as $membership)
                            <tr>
                                <td>{{ $membership['starts_on'] }}</td>
                                <td>{{ $membership['ends_on'] }}</td>
                                <td>
                                    <x-vdbs.status :type="$membership['status_type']">
                                        {{ $membership['status'] }}
                                    </x-vdbs.status>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
