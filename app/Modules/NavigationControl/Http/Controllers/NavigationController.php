<?php

namespace App\Modules\NavigationControl\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\NavigationControl\Support\NavigationControl;
use App\Support\PortalNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Bereiche und Seiten des Kopfmenüs ein- und ausblenden. Berechtigung navigation.manage. */
class NavigationController extends Controller
{
    public function edit(): View
    {
        return view('navigation.edit', ['areas' => $this->tree()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $areas = (array) $request->input('areas', []);
        $pages = (array) $request->input('pages', []);
        $hidden = [];

        foreach ($this->tree() as $entry) {
            if (! isset($areas[$entry['area']->value])) {
                $hidden[] = NavigationControl::areaKey($entry['area']);
            }

            foreach ($entry['pages'] as $page) {
                if (! isset($pages[$page['token']])) {
                    $hidden[] = $page['key'];
                }
            }
        }

        NavigationControl::replace($hidden);
        AuditLog::record(AuditEvent::NavigationSaved, $request->user(), null, ['ausgeblendet' => count($hidden)]);

        return redirect()->route('navigation.edit')->with('success', 'Die Navigation wurde gespeichert.');
    }

    /**
     * Alle Bereiche, die sich ausblenden lassen, mit ihren Seiten (Gruppen als Überschrift).
     *
     * @return list<array{area: \App\Support\PortalAreas, enabled: bool, pages: list<array{key: string, token: string, label: string, group: ?string, enabled: bool}>}>
     */
    private function tree(): array
    {
        $catalog = PortalNavigation::catalog();
        $tree = [];

        foreach (NavigationControl::togglableAreas() as $area) {
            $rows = [];
            $walk = function (array $pages, ?string $group) use (&$walk, &$rows, $area): void {
                foreach ($pages as $page) {
                    if (($page['group'] ?? false) === true) {
                        $walk($page['children'] ?? [], (string) $page['label']);

                        continue;
                    }

                    if (! NavigationControl::pinned($page)) {
                        $key = NavigationControl::pageKey($area, $page);
                        $rows[] = ['key' => $key, 'token' => substr(md5($key), 0, 12), 'label' => (string) $page['label'], 'group' => $group, 'enabled' => NavigationControl::pageEnabled($area, $page)];
                    }

                    $walk($page['children'] ?? [], $group);
                }
            };
            $walk($catalog[$area->value]['pages'], null);
            $tree[] = ['area' => $area, 'enabled' => NavigationControl::areaEnabled($area), 'pages' => $rows];
        }

        return $tree;
    }
}
