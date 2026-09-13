<?php

namespace App\View\Components\Vdbs;

use App\Modules\Identity\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

final class PortalHeader extends Component
{
    /**
     * @param  list<array<string, mixed>>  $areas
     * @param  list<array<string, mixed>>  $navigation
     * @param  list<array<string, mixed>>  $breadcrumbs
     * @param  array<string, mixed>|null  $account
     */
    public function __construct(
        public string $area = 'VDBS Portal',
        public string $pageTitle = 'Start',
        public string $homeUrl = '#',
        public ?string $areaUrl = null,
        public ?string $logoUrl = null,
        public array $areas = [],
        public array $navigation = [],
        public array $breadcrumbs = [],
        public ?string $loginUrl = null,
        public ?array $account = null,
        public bool $preview = false,
        public bool $wide = false,
    ) {
        if ($this->account === null || ($this->account['avatar_url'] ?? null) !== null) {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $this->account['avatar_url'] = $user->avatarUrl();
    }

    public function render(): View|Closure|string
    {
        return view('components.vdbs.portal-header');
    }
}
