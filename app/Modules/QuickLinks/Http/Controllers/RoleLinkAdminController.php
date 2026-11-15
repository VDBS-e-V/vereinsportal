<?php

namespace App\Modules\QuickLinks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\QuickLinks\Models\RoleLink;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Je Rolle bis zu drei wichtige Links mit einer Wichtigkeit (Zahl). Berechtigung role_links.manage. */
class RoleLinkAdminController extends Controller
{
    public function edit(): View
    {
        $links = RoleLink::query()->orderBy('priority')->orderBy('id')->get()->groupBy('role_key');

        return view('role-links.edit', ['roles' => $this->roles(), 'links' => $links, 'perRole' => RoleLink::PER_ROLE]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'links' => ['nullable', 'array'],
            'links.*' => ['array', 'max:'.RoleLink::PER_ROLE],
            'links.*.*.label' => ['nullable', 'string', 'max:60'],
            'links.*.*.url' => ['nullable', 'string', 'max:255', 'regex:#^(/(?!/)|https://)\S*$#'],
            'links.*.*.priority' => ['nullable', 'integer', 'min:1', 'max:99'],
        ], ['links.*.*.url.regex' => 'Die Adresse muss mit / (Seite im Portal) oder https:// beginnen.', 'links.*.*.label.max' => 'Der Titel darf höchstens 60 Zeichen lang sein.']);

        $rows = [];

        foreach ((array) $request->input('links', []) as $role => $entries) {
            if (! array_key_exists($role, $this->roles())) {
                throw ValidationException::withMessages(['links' => 'Unbekannte Rolle.']);
            }

            foreach ((array) $entries as $index => $entry) {
                $label = trim((string) ($entry['label'] ?? ''));
                $url = trim((string) ($entry['url'] ?? ''));

                if ($label === '' && $url === '') {
                    continue;
                }

                if ($label === '' || $url === '') {
                    throw ValidationException::withMessages(['links.'.$role.'.'.$index.'.label' => 'Bitte geben Sie Titel und Adresse an (oder lassen Sie beides leer).']);
                }

                $rows[] = ['role_key' => $role, 'label' => $label, 'url' => $url, 'priority' => (int) ($entry['priority'] ?? 1) ?: 1, 'created_at' => now(), 'updated_at' => now()];
            }
        }

        DB::transaction(function () use ($rows): void {
            RoleLink::query()->delete();
            RoleLink::query()->insert($rows);
        });

        AuditLog::record(AuditEvent::RoleLinksSaved, $this->user(), null, ['anzahl' => count($rows)]);

        return redirect()->route('role-links.edit')->with('success', 'Die Schnellzugriffe wurden gespeichert.');
    }

    /** @return array<string, string> */
    private function roles(): array
    {
        return collect(RoleKey::cases())->reject(fn (RoleKey $role): bool => $role === RoleKey::Guest)->mapWithKeys(fn (RoleKey $role): array => [$role->value => $role->label()])->all();
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
