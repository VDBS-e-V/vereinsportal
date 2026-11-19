<?php

namespace App\Http\Controllers;

use App\Support\PortalAreas;
use App\Support\PortalNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Startseiten der Bereiche: Begrüßung und die Seiten des Bereichs, die die Person sehen darf. */
class PortalAreaController extends Controller
{
    public function area(Request $request, string $area): View
    {
        $area = PortalAreas::from($area);
        $user = $request->user();

        abort_unless($user !== null && in_array($area, PortalNavigation::visibleAreas($user), true), 403);

        $routeName = $request->route()?->getName();
        $pages = array_values(array_filter(PortalNavigation::pages($area, $user, $routeName), fn (array $page): bool => ! $page['current']));

        return view('portal.area', [
            'area' => $area,
            'pages' => $pages,
        ]);
    }
}
