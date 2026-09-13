<?php

namespace App\Support;

use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Route;

final class PortalAreaCatalog
{
    public const START = 'start';

    public const PROFILE = 'profile';

    public const ADMINISTRATION = 'administration';

    public const BOARD = 'board';

    public const COORDINATION = 'coordination';

    public const DESIGN = 'design';

    public function __construct(
        private readonly AdministrationAccess $administrationAccess,
    ) {}

    /**
     * Return all currently available portal areas for a user, including
     * personal areas that intentionally stay hidden from the global switcher.
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     url: string,
     *     visible_in_switcher: bool,
     *     active: bool
     * }>
     */
    public function areas(
        ?User $user,
        ?string $activeKey = null,
        bool $includeDesign = true,
    ): array {
        $areas = [];

        if ($user !== null) {
            $areas[] = $this->area(
                self::START,
                'Start',
                'my.home',
                false,
                $activeKey,
            );
            $areas[] = $this->area(
                self::PROFILE,
                'Mein Profil',
                'my.account.profile',
                false,
                $activeKey,
            );
        }

        $this->appendCapabilityArea(
            $areas,
            $user,
            AdministrationCapability::AdministrationAreaAccess,
            self::ADMINISTRATION,
            'Verwaltung',
            'administration.home',
            $activeKey,
        );
        $this->appendCapabilityArea(
            $areas,
            $user,
            AdministrationCapability::BoardAreaAccess,
            self::BOARD,
            'Vorstand',
            'board.home',
            $activeKey,
        );
        $this->appendCapabilityArea(
            $areas,
            $user,
            AdministrationCapability::CoordinationAreaAccess,
            self::COORDINATION,
            'Koordination',
            'coordination.home',
            $activeKey,
        );

        if ($includeDesign && Route::has('design.index')) {
            $areas[] = $this->area(
                self::DESIGN,
                'Design',
                'design.index',
                true,
                $activeKey,
            );
        }

        return $areas;
    }

    /**
     * Return only entries that belong in the global area switcher.
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     url: string,
     *     visible_in_switcher: bool,
     *     active: bool
     * }>
     */
    public function switcherAreas(
        ?User $user,
        ?string $activeKey = null,
        bool $includeDesign = true,
    ): array {
        return array_values(
            array_filter(
                $this->areas($user, $activeKey, $includeDesign),
                static fn (array $area): bool => $area['visible_in_switcher'],
            ),
        );
    }

    /**
     * @param  list<array{
     *     key: string,
     *     label: string,
     *     url: string,
     *     visible_in_switcher: bool,
     *     active: bool
     * }>  $areas
     */
    private function appendCapabilityArea(
        array &$areas,
        ?User $user,
        AdministrationCapability $capability,
        string $key,
        string $label,
        string $routeName,
        ?string $activeKey,
    ): void {
        if (
            ! Route::has($routeName)
            || ! $this->administrationAccess->allowsCapability(
                $user,
                $capability,
            )
        ) {
            return;
        }

        $areas[] = $this->area(
            $key,
            $label,
            $routeName,
            true,
            $activeKey,
        );
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     url: string,
     *     visible_in_switcher: bool,
     *     active: bool
     * }
     */
    private function area(
        string $key,
        string $label,
        string $routeName,
        bool $visibleInSwitcher,
        ?string $activeKey,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'url' => route($routeName),
            'visible_in_switcher' => $visibleInSwitcher,
            'active' => $activeKey === $key,
        ];
    }
}
