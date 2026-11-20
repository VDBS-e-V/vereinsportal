<?php

namespace App\Modules\Coordinations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Coordinations\Models\Coordination;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Pflege der Koordinationen. Eine Koordination mit Schulen wird nicht gelöscht, sondern auf „inaktiv“ gesetzt. */
class CoordinationAdminController extends Controller
{
    public function index(): View
    {
        return view('coordinations.index', ['coordinations' => Coordination::query()->withCount('partners')->ordered()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $coordination = Coordination::query()->create($data);

        AuditLog::record(AuditEvent::CoordinationSaved, $this->user(), null, ['name' => $coordination->name, 'neu' => true]);

        return redirect()->route('coordinations.index')->with('success', 'Die Koordination wurde angelegt.');
    }

    public function update(Request $request, Coordination $coordination): RedirectResponse
    {
        $coordination->update($this->validated($request, $coordination));

        AuditLog::record(AuditEvent::CoordinationSaved, $this->user(), null, ['name' => $coordination->name, 'neu' => false]);

        return redirect()->route('coordinations.index')->with('success', 'Die Koordination wurde gespeichert.');
    }

    public function destroy(Coordination $coordination): RedirectResponse
    {
        if ($coordination->partners()->exists()) {
            return redirect()->route('coordinations.index')->withErrors(['coordination' => 'Dieser Koordination sind noch Schulen zugeordnet. Setzen Sie sie stattdessen auf „inaktiv“.']);
        }

        $name = $coordination->name;
        $coordination->delete();

        AuditLog::record(AuditEvent::CoordinationDeleted, $this->user(), null, ['name' => $name]);

        return redirect()->route('coordinations.index')->with('success', 'Die Koordination wurde gelöscht.');
    }

    /** @return array{name: string, active: bool, sort_order: int} */
    private function validated(Request $request, ?Coordination $coordination): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('coordinations', 'name')->ignore($coordination?->id)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], ['name.required' => 'Bitte geben Sie einen Namen an.', 'name.unique' => 'Eine Koordination mit diesem Namen gibt es schon.']);

        return ['name' => trim((string) $data['name']), 'active' => $request->boolean('active', true), 'sort_order' => (int) ($data['sort_order'] ?? 0)];
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
