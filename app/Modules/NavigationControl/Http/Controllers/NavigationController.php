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
    private const CUSTOM_SLOTS = 3;

    private const CHILD_SLOTS = 3;

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
        $rows = collect($this->orderedAreas())->map(fn (PortalAreas $area): array => ['area' => $area, 'pages' => collect($this->catalogRows($area))->pluck('title')->implode(', ')]);

        return view('navigation.headers', ['rows' => $rows]);
    }

    public function header(string $area): View
    {
        $area = PortalAreas::from($area);

        return view('navigation.header', ['area' => $area, 'rows' => $this->catalogRows($area), 'custom' => $this->customRows($area), 'customSlots' => self::CUSTOM_SLOTS, 'childSlots' => self::CHILD_SLOTS]);
    }

    public function updateHeader(Request $request, string $area): RedirectResponse
    {
        $area = PortalAreas::from($area);
        $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.label' => ['nullable', 'string', 'max:60'],
            'items.*.sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'custom' => ['nullable', 'array', 'max:'.self::CUSTOM_SLOTS],
            'custom.*.label' => ['nullable', 'string', 'max:60'],
            'custom.*.url' => ['nullable', 'string', 'max:255', 'regex:#^(/(?!/)|https://)\S*$#'],
            'custom.*.children' => ['nullable', 'array', 'max:'.self::CHILD_SLOTS],
            'custom.*.children.*.label' => ['nullable', 'string', 'max:60'],
            'custom.*.children.*.url' => ['nullable', 'string', 'max:255', 'regex:#^(/(?!/)|https://)\S*$#'],
        ], ['custom.*.url.regex' => 'Die Adresse muss mit / (Seite im Portal) oder https:// beginnen.', 'custom.*.children.*.url.regex' => 'Die Adresse muss mit / (Seite im Portal) oder https:// beginnen.']);

        $catalog = [];

        foreach ($this->catalogRows($area) as $row) {
            $input = (array) $request->input('items.'.$row['token'], []);
            $catalog[] = ['key' => $row['key'], 'label' => filled($input['label'] ?? null) ? trim((string) $input['label']) : null, 'sort' => ($input['sort'] ?? '') === '' ? null : (int) $input['sort'], 'visible' => $row['pinned'] || isset($input['visible'])];
        }

        $custom = [];

        foreach ((array) $request->input('custom', []) as $index => $entry) {
            $label = trim((string) ($entry['label'] ?? ''));
            $url = trim((string) ($entry['url'] ?? ''));
            $children = [];

            foreach ((array) ($entry['children'] ?? []) as $childIndex => $child) {
                $childLabel = trim((string) ($child['label'] ?? ''));
                $childUrl = trim((string) ($child['url'] ?? ''));

                if ($childLabel === '' && $childUrl === '') {
                    continue;
                }

                if ($childLabel === '' || $childUrl === '') {
                    throw ValidationException::withMessages(['custom.'.$index.'.children.'.$childIndex.'.label' => 'Bitte geben Sie Titel und Adresse der Unterseite an (oder lassen Sie beides leer).']);
                }

                $children[] = ['label' => $childLabel, 'url' => $childUrl];
            }

            if ($label === '' && $url === '' && $children === []) {
                continue;
            }

            if ($label === '' || $url === '') {
                throw ValidationException::withMessages(['custom.'.$index.'.label' => 'Bitte geben Sie Titel und Adresse an (oder lassen Sie beides leer).']);
            }

            $custom[] = ['label' => $label, 'url' => $url, 'children' => $children];
        }

        NavigationControl::saveItems($area, $catalog, $custom);
        AuditLog::record(AuditEvent::NavigationSaved, $request->user(), null, ['bereich' => $area->value, 'eigene_links' => count($custom)]);

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

    /**
     * Seiten des Katalogs für einen Bereich (Gruppen aufgelöst) mit den gespeicherten Änderungen.
     *
     * @return list<array{key: string, token: string, title: string, label: ?string, sort: ?int, visible: bool, pinned: bool}>
     */
    private function catalogRows(PortalAreas $area): array
    {
        $rows = [];

        foreach (PortalNavigation::catalog()[$area->value]['pages'] as $page) {
            $key = NavigationControl::pageKey($page);
            $item = NavigationControl::item($area, $key);
            $rows[] = ['key' => $key, 'token' => substr(md5($key), 0, 12), 'title' => (string) $page['label'], 'label' => $item?->label, 'sort' => $item?->sort, 'visible' => $item?->visible ?? true, 'pinned' => NavigationControl::pinned($page) || ($page['group'] ?? false) === true];
        }

        return $rows;
    }

    /** @return list<array{label: string, url: string, children: list<array{label: string, url: string}>}> */
    private function customRows(PortalAreas $area): array
    {
        $items = \App\Modules\NavigationControl\Models\NavigationItem::query()->where('area', $area->value)->whereNull('page_key')->orderBy('sort')->get();

        return $items->whereNull('parent_id')->values()->map(fn ($item): array => ['label' => (string) $item->label, 'url' => (string) $item->url, 'children' => $items->where('parent_id', $item->id)->sortBy('sort')->values()->map(fn ($child): array => ['label' => (string) $child->label, 'url' => (string) $child->url])->all()])->all();
    }
}
