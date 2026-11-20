<?php

namespace App\Modules\NavigationControl\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\NavigationControl\Models\NavigationArea;
use App\Modules\NavigationControl\Support\NavigationControl;
use App\Support\PortalAreas;
use App\Support\PortalNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Web-Control: Bereiche (Reihenfolge, Sichtbarkeit, Rollen) und Header (Seiten und eigene Links je Bereich). Berechtigung navigation.manage. */
class NavigationController extends Controller
{
    private const MAX_ENTRIES = 40;

    public function edit(Request $request): View
    {
        $q = mb_strtolower(trim((string) $request->query('q')));
        $areas = collect($this->orderedAreas())->filter(fn (PortalAreas $area): bool => $q === '' || str_contains(mb_strtolower(NavigationControl::areaLabel($area)), $q))->values();

        return view('navigation.areas', ['areas' => $areas, 'q' => (string) $request->query('q'), 'home' => fn (PortalAreas $area): string => route(PortalNavigation::catalog()[$area->value]['home'])]);
    }

    public function area(string $area): View
    {
        $area = PortalAreas::from($area);

        return view('navigation.area', ['area' => $area, 'row' => NavigationArea::query()->where('area', $area->value)->first(), 'roles' => $this->roles()]);
    }

    public function updateArea(Request $request, string $area): RedirectResponse
    {
        $area = PortalAreas::from($area);
        $data = $request->validate(['label' => ['nullable', 'string', 'max:60'], 'roles' => ['nullable', 'array'], 'roles.*' => ['string']]);

        $roles = array_values(array_intersect((array) ($data['roles'] ?? []), array_keys($this->roles())));
        $this->row($area)->update(['label' => filled($data['label'] ?? null) ? trim((string) $data['label']) : null, 'show_header' => $request->boolean('show_header'), 'show_lists' => $request->boolean('show_lists'), 'roles' => $roles === [] ? null : $roles]);
        NavigationControl::reset();
        AuditLog::record(AuditEvent::NavigationSaved, $request->user(), null, ['bereich' => $area->value]);

        return redirect()->route('navigation.edit')->with('success', 'Der Bereich „'.NavigationControl::areaLabel($area).'“ wurde gespeichert.');
    }

    public function move(Request $request, string $area): RedirectResponse
    {
        $request->validate(['direction' => ['required', 'in:up,down']]);
        $order = $this->orderedAreas();
        $index = array_search(PortalAreas::from($area), $order, true);
        $swap = $request->input('direction') === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && isset($order[$swap])) {
            [$order[$index], $order[$swap]] = [$order[$swap], $order[$index]];

            foreach ($order as $position => $item) {
                $this->row($item)->update(['sort' => $position * 10]);
            }

            NavigationControl::reset();
            AuditLog::record(AuditEvent::NavigationSaved, $request->user(), null, ['bereich' => $area, 'verschoben' => $request->input('direction')]);
        }

        return redirect()->route('navigation.edit');
    }

    public function toggle(Request $request, string $area): RedirectResponse
    {
        $request->validate(['field' => ['required', 'in:show_header,show_lists']]);
        $row = $this->row(PortalAreas::from($area));
        $field = (string) $request->input('field');
        $row->update([$field => ! $row->{$field}]);
        NavigationControl::reset();
        AuditLog::record(AuditEvent::NavigationSaved, $request->user(), null, ['bereich' => $area, 'feld' => $field]);

        return redirect()->route('navigation.edit');
    }

    public function headers(): View
    {
        $rows = collect($this->orderedAreas())->map(fn (PortalAreas $area): array => ['area' => $area, 'pages' => collect(NavigationControl::layout($area, PortalNavigation::catalog()[$area->value]['pages']))->where('visible', true)->map(fn (array $row): string => $row['label'] !== '' ? $row['label'] : $row['title'])->implode(', ')]);

        return view('navigation.headers', ['rows' => $rows]);
    }

    public function header(string $area): View
    {
        $area = PortalAreas::from($area);

        return view('navigation.header', ['area' => $area, 'layout' => NavigationControl::layout($area, PortalNavigation::catalog()[$area->value]['pages'])]);
    }

    public function updateHeader(Request $request, string $area): RedirectResponse
    {
        $area = PortalAreas::from($area);
        $catalog = collect(NavigationControl::layout($area, PortalNavigation::catalog()[$area->value]['pages']))->where('kind', 'catalog')->keyBy('ref');
        $input = json_decode((string) $request->input('layout'), true);

        if (! is_array($input) || count($input) > self::MAX_ENTRIES) {
            throw ValidationException::withMessages(['layout' => 'Der Aufbau des Headers ist ungültig. Bitte laden Sie die Seite neu.']);
        }

        $rows = [];
        $seen = [];

        foreach ($input as $entry) {
            $entry = (array) $entry;
            $ref = (string) ($entry['ref'] ?? '');
            $kind = (string) ($entry['kind'] ?? '');
            $label = trim((string) ($entry['label'] ?? ''));
            $url = trim((string) ($entry['url'] ?? ''));

            if ($ref === '' || isset($seen[$ref]) || ! in_array($kind, ['catalog', 'custom'], true) || mb_strlen($label) > 60 || ($kind === 'catalog' && ! $catalog->has($ref))) {
                throw ValidationException::withMessages(['layout' => 'Der Aufbau des Headers ist ungültig. Bitte laden Sie die Seite neu.']);
            }

            if ($kind === 'custom' && ($label === '' || $url === '')) {
                throw ValidationException::withMessages(['layout' => 'Bitte geben Sie bei jedem eigenen Link Titel und Adresse an.']);
            }

            if ($kind === 'custom' && (! preg_match('#^(/(?!/)|https://)\S*$#', $url) || mb_strlen($url) > 255)) {
                throw ValidationException::withMessages(['layout' => 'Die Adresse von „'.$label.'“ muss mit / (Seite im Portal) oder https:// beginnen.']);
            }

            $seen[$ref] = true;
            $rows[] = ['ref' => $ref, 'kind' => $kind, 'label' => $label, 'url' => $url, 'visible' => $kind === 'catalog' && $catalog[$ref]['pinned'] ? true : (bool) ($entry['visible'] ?? true), 'parent' => filled($entry['parent'] ?? null) ? (string) $entry['parent'] : null];
        }

        // Nicht gesendete Seiten des Katalogs bleiben, wie sie sind
        foreach ($catalog as $ref => $row) {
            if (! isset($seen[$ref])) {
                $rows[] = ['ref' => (string) $ref, 'kind' => 'catalog', 'label' => $row['label'], 'url' => '', 'visible' => $row['visible'], 'parent' => null];
            }
        }

        // Unterseiten: nur eine Ebene, die übergeordnete Seite muss vorhanden sein und darf selbst keine sein
        $byRef = collect($rows)->keyBy('ref');
        $nested = collect($rows)->filter(fn (array $row): bool => $row['parent'] !== null);

        foreach ($nested as $row) {
            $parent = $byRef->get($row['parent']);
            $isParent = $nested->contains(fn (array $other): bool => $other['parent'] === $row['ref']);
            $allowed = $parent !== null && $parent['parent'] === null && $row['parent'] !== $row['ref'] && ! $isParent && ($row['kind'] !== 'catalog' || $catalog[$row['ref']]['nestable']) && ($parent['kind'] !== 'catalog' || $catalog[$parent['ref']]['parentable']);

            if (! $allowed) {
                throw ValidationException::withMessages(['layout' => 'Eine Seite kann nur unter einer Hauptseite stehen, und Gruppen oder Seiten mit eigenen Unterseiten lassen sich nicht einordnen.']);
            }
        }

        NavigationControl::saveLayout($area, $rows);
        AuditLog::record(AuditEvent::NavigationSaved, $request->user(), null, ['bereich' => $area->value, 'eintraege' => count($rows)]);

        return redirect()->route('navigation.header', $area->value)->with('success', 'Der Header „'.NavigationControl::areaLabel($area).'“ wurde gespeichert.');
    }

    /** @return list<PortalAreas> Bereiche in der Reihenfolge laut Web-Control. */
    private function orderedAreas(): array
    {
        $areas = NavigationControl::togglableAreas();
        $order = array_flip(array_map(fn (PortalAreas $area): string => $area->value, $areas));
        usort($areas, fn (PortalAreas $a, PortalAreas $b): int => NavigationControl::areaSort($a, $order[$a->value]) <=> NavigationControl::areaSort($b, $order[$b->value]));

        return $areas;
    }

    private function row(PortalAreas $area): NavigationArea
    {
        return NavigationArea::query()->firstOrCreate(['area' => $area->value], ['show_header' => true, 'show_lists' => true]);
    }

    /** @return array<string, string> */
    private function roles(): array
    {
        return collect(RoleKey::cases())->reject(fn (RoleKey $role): bool => $role === RoleKey::Guest)->mapWithKeys(fn (RoleKey $role): array => [$role->value => $role->label()])->all();
    }
}
