<?php

namespace App\Modules\AccessGovernance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\TwoFactorManager;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Services\UserAdministration;
use App\Modules\AccessGovernance\Support\Capabilities;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Nutzerverwaltung: Liste, Details, Anlegen mit Einladung, Sperren, Zwei-Faktor zurücksetzen. */
class UserAdministrationController extends Controller
{
    private const PER_PAGE = 15;

    private const SORTS = ['name', 'email', 'status', 'last_login'];

    public function __construct(
        private readonly UserAdministration $administration,
        private readonly TwoFactorManager $twoFactor,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = UserStatus::tryFrom((string) $request->query('status', ''));
        $roleKey = RoleKey::tryFrom((string) $request->query('role', ''));
        $sort = in_array($request->query('sort'), self::SORTS, true) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $users = User::query()
            ->leftJoin('persons', 'persons.id', '=', 'users.person_id')
            ->select('users.*')
            ->with(['person', 'activeRoleAssignments.role'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn ($inner) => $inner
                    ->where('users.email', 'like', $like)
                    ->orWhere('persons.first_name', 'like', $like)
                    ->orWhere('persons.last_name', 'like', $like));
            })
            ->when($status !== null, fn ($query) => $query->where('users.status', $status?->value))
            ->when($roleKey !== null, fn ($query) => $query->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', $roleKey?->value))));

        match ($sort) {
            'email' => $users->orderBy('users.email', $direction),
            'status' => $users->orderBy('users.status', $direction)->orderBy('persons.last_name'),
            'last_login' => $users->orderByRaw('users.last_login_at IS NULL')->orderBy('users.last_login_at', $direction),
            default => $users->orderBy('persons.last_name', $direction)->orderBy('persons.first_name', $direction)->orderBy('users.email'),
        };

        $paginator = $users->paginate(self::PER_PAGE)->withQueryString();

        $sortLink = fn (string $key): string => $request->fullUrlWithQuery([
            'sort' => $key,
            'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc',
            'page' => null,
        ]);

        $columns = [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'href' => $sortLink('name')],
            ['key' => 'email', 'label' => 'E-Mail', 'sortable' => true, 'href' => $sortLink('email')],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'href' => $sortLink('status')],
            ['key' => 'roles', 'label' => 'Rollen'],
            ['key' => 'last_login', 'label' => 'Letzte Anmeldung', 'sortable' => true, 'href' => $sortLink('last_login')],
        ];

        $active = collect([
            $search !== '' ? ['label' => 'Suche: '.$search, 'href' => $request->fullUrlWithoutQuery(['q', 'page'])] : null,
            $status !== null ? ['label' => 'Status: '.$status->label(), 'href' => $request->fullUrlWithoutQuery(['status', 'page'])] : null,
            $roleKey !== null ? ['label' => 'Rolle: '.$roleKey->label(), 'href' => $request->fullUrlWithoutQuery(['role', 'page'])] : null,
        ])->filter()->values()->all();

        $withoutTwoFactor = User::query()
            ->where('status', UserStatus::Active->value)
            ->whereDoesntHave('twoFactorMethods', fn ($methods) => $methods->whereNotNull('confirmed_at')->whereNull('disabled_at'))
            ->count();

        return view('admin.users.index', [
            'users' => $paginator,
            'columns' => $columns,
            'sort' => $sort,
            'direction' => $direction,
            'active' => $active,
            'filters' => ['q' => $search, 'status' => $status?->value, 'role' => $roleKey?->value],
            'statusOptions' => collect(UserStatus::cases())->mapWithKeys(fn (UserStatus $case): array => [$case->value => $case->label()])->all(),
            'roleOptions' => collect(RoleKey::cases())->mapWithKeys(fn (RoleKey $case): array => [$case->value => $case->label()])->all(),
            'stats' => [
                'active' => User::query()->where('status', UserStatus::Active->value)->count(),
                'invited' => User::query()->where('status', UserStatus::PendingVerification->value)->count(),
                'disabled' => User::query()->where('status', UserStatus::Disabled->value)->count(),
                'withoutTwoFactor' => $withoutTwoFactor,
            ],
            'canManage' => Gate::allows('users.manage'),
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $user->load(['person', 'roleAssignments.role', 'roleAssignments.grantedBy.person']);

        $now = now();
        $assignments = $user->roleAssignments->sortByDesc('starts_at');
        $isActive = fn ($assignment): bool => $assignment->starts_at <= $now && ($assignment->ends_at === null || $assignment->ends_at > $now);

        $actor = $request->user();
        $heldRoleIds = $assignments->filter($isActive)->pluck('role_id')->all();

        $assignable = Role::query()->orderBy('name')->get()
            ->filter(fn (Role $role): bool => ! in_array($role->id, $heldRoleIds, true)
                && ($key = RoleKey::tryFrom($role->key)) !== null
                && $actor instanceof User
                && Capabilities::canAssign($actor, $key));

        return view('admin.users.show', [
            'user' => $user,
            'person' => $user->person,
            'currentAssignments' => $assignments->filter($isActive)->values(),
            'pastAssignments' => $assignments->reject($isActive)->values(),
            'assignable' => $assignable,
            'twoFactorMethods' => $this->twoFactor->activeMethods($user)->map(fn ($method) => $method->type->value)->all(),
            'remainingCodes' => $this->twoFactor->remainingRecoveryCodes($user),
            'canManage' => Gate::allows('users.manage'),
            'canAssign' => Gate::allows('roles.assign'),
            'isSelf' => $user->is($actor),
        ]);
    }

    public function create(Request $request): View
    {
        $actor = $request->user();

        return view('admin.users.create', [
            'roleOptions' => collect(RoleKey::cases())
                ->reject(fn (RoleKey $key): bool => $key === RoleKey::Guest || ! ($actor instanceof User && Capabilities::canAssign($actor, $key)))
                ->mapWithKeys(fn (RoleKey $key): array => [$key->value => $key->label()])
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'email' => ['required', 'string', 'email', 'max:254', Rule::unique('users', 'email'), Rule::unique('persons', 'email')],
            'role' => ['nullable', Rule::enum(RoleKey::class)],
        ], [
            'first_name.required' => 'Bitte geben Sie den Vornamen ein.',
            'last_name.required' => 'Bitte geben Sie den Nachnamen ein.',
            'birth_date.required' => 'Bitte geben Sie das Geburtsdatum ein.',
            'birth_date.before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'email.required' => 'Bitte geben Sie die E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
        ]);

        $role = filled($data['role'] ?? null) ? RoleKey::from($data['role']) : null;

        abort_if($role !== null && ! Capabilities::canAssign($actor, $role), 403);

        $user = $this->administration->create([
            'title' => $data['title'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birth_date' => $data['birth_date'],
            'email' => mb_strtolower(trim($data['email'])),
        ], $actor, $role);

        return redirect()->route('admin.users.show', $user)->with('success', 'Das Konto wurde angelegt und die Einladung an '.$user->email.' gesendet.');
    }

    public function invite(User $user): RedirectResponse
    {
        return $this->perform(fn () => $this->administration->invite($user), $user, 'Die Einladung wurde erneut gesendet.');
    }

    public function disable(Request $request, User $user): RedirectResponse
    {
        return $this->perform(fn () => $this->administration->disable($user, $this->actor($request)), $user, 'Das Konto wurde gesperrt. Alle Sitzungen sind beendet.');
    }

    public function enable(Request $request, User $user): RedirectResponse
    {
        return $this->perform(fn () => $this->administration->enable($user, $this->actor($request)), $user, 'Das Konto wurde entsperrt.');
    }

    public function resetTwoFactor(Request $request, User $user): RedirectResponse
    {
        return $this->perform(fn () => $this->administration->resetTwoFactor($user, $this->actor($request)), $user, 'Die Zwei-Faktor-Methoden wurden entfernt. Der Nutzer kann sie neu einrichten.');
    }

    public function passwordLink(Request $request, User $user): RedirectResponse
    {
        return $this->perform(fn () => $this->administration->sendPasswordLink($user, $this->actor($request)), $user, 'Der Link zum Zurücksetzen des Passworts wurde gesendet.');
    }

    private function perform(callable $action, User $user, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $exception) {
            return redirect()->route('admin.users.show', $user)->with('warning', $exception->getMessage());
        }

        return redirect()->route('admin.users.show', $user)->with('success', $success);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
