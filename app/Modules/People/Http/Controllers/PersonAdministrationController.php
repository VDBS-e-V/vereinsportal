<?php

namespace App\Modules\People\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use App\Modules\People\Services\PersonAdministration;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Personenverwaltung: Liste, Details, Anlegen, Bearbeiten, Löschen und Konto aus Person erstellen. */
class PersonAdministrationController extends Controller
{
    private const PER_PAGE = 15;

    private const SORTS = ['name', 'email', 'city', 'birth_date'];

    private const ACCOUNT_FILTERS = ['with' => 'Mit Konto', 'without' => 'Ohne Konto'];

    public function __construct(private readonly PersonAdministration $people) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $account = array_key_exists((string) $request->query('account', ''), self::ACCOUNT_FILTERS) ? (string) $request->query('account') : null;
        $sort = in_array($request->query('sort'), self::SORTS, true) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $persons = Person::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn ($inner) => $inner
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('city', 'like', $like));
            })
            ->when($account === 'with', fn ($query) => $query->whereHas('user'))
            ->when($account === 'without', fn ($query) => $query->whereDoesntHave('user'));

        match ($sort) {
            'email' => $persons->orderBy('email', $direction),
            'city' => $persons->orderBy('city', $direction)->orderBy('last_name'),
            'birth_date' => $persons->orderBy('birth_date', $direction)->orderBy('last_name'),
            default => $persons->orderBy('last_name', $direction)->orderBy('first_name', $direction)->orderBy('id'),
        };

        $paginator = $persons->paginate(self::PER_PAGE)->withQueryString();

        $sortLink = fn (string $key): string => $request->fullUrlWithQuery([
            'sort' => $key,
            'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc',
            'page' => null,
        ]);

        $columns = [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'href' => $sortLink('name')],
            ['key' => 'email', 'label' => 'E-Mail', 'sortable' => true, 'href' => $sortLink('email')],
            ['key' => 'city', 'label' => 'Ort', 'sortable' => true, 'href' => $sortLink('city')],
            ['key' => 'birth_date', 'label' => 'Geburtsdatum', 'sortable' => true, 'href' => $sortLink('birth_date')],
            ['key' => 'account', 'label' => 'Konto'],
        ];

        $active = collect([
            $search !== '' ? ['label' => 'Suche: '.$search, 'href' => $request->fullUrlWithoutQuery(['q', 'page'])] : null,
            $account !== null ? ['label' => self::ACCOUNT_FILTERS[$account], 'href' => $request->fullUrlWithoutQuery(['account', 'page'])] : null,
        ])->filter()->values()->all();

        $total = Person::query()->count();
        $withAccount = Person::query()->whereHas('user')->count();

        return view('admin.people.index', [
            'persons' => $paginator,
            'columns' => $columns,
            'sort' => $sort,
            'direction' => $direction,
            'active' => $active,
            'filters' => ['q' => $search, 'account' => $account],
            'accountOptions' => self::ACCOUNT_FILTERS,
            'stats' => ['total' => $total, 'withAccount' => $withAccount, 'withoutAccount' => $total - $withAccount],
            'canManage' => Gate::allows('people.manage'),
            'canViewUsers' => Gate::allows('users.view'),
        ]);
    }

    public function show(Person $person): View
    {
        $person->load('user');

        return view('admin.people.show', [
            'person' => $person,
            'user' => $person->user,
            'canManage' => Gate::allows('people.manage'),
            'canManageUsers' => Gate::allows('users.manage'),
            'canViewUsers' => Gate::allows('users.view'),
            'history' => Gate::allows('audit.view') ? AuditEntry::query()->where('subject_type', 'person')->where('subject_id', $person->id)->orderByDesc('occurred_at')->orderByDesc('id')->limit(8)->get() : null,
        ]);
    }

    public function create(): View
    {
        return view('admin.people.form', ['person' => null, 'hasAccount' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(null), $this->messages());

        $person = $this->people->create($data, $this->actor($request));

        return redirect()->route('admin.people.show', $person)->with('success', 'Die Person wurde angelegt.');
    }

    public function edit(Person $person): View
    {
        return view('admin.people.form', ['person' => $person, 'hasAccount' => $person->user()->exists()]);
    }

    public function update(Request $request, Person $person): RedirectResponse
    {
        $data = $request->validate($this->rules($person), $this->messages());

        $this->people->update($person, $data, $this->actor($request));

        return redirect()->route('admin.people.show', $person)->with('success', 'Die Angaben wurden gespeichert.');
    }

    public function destroy(Request $request, Person $person): RedirectResponse
    {
        try {
            $this->people->delete($person, $this->actor($request));
        } catch (DomainException $exception) {
            return redirect()->route('admin.people.show', $person)->with('warning', $exception->getMessage());
        }

        return redirect()->route('admin.people.index')->with('success', 'Die Person wurde gelöscht.');
    }

    public function createAccount(Request $request, Person $person): RedirectResponse
    {
        try {
            $user = $this->people->createAccount($person, $this->actor($request));
        } catch (DomainException $exception) {
            return redirect()->route('admin.people.show', $person)->with('warning', $exception->getMessage());
        }

        return redirect()->route('admin.people.show', $person)->with('success', 'Das Konto wurde angelegt und die Einladung an '.$user->email.' gesendet.');
    }

    /** @return array<string, mixed> */
    private function rules(?Person $person): array
    {
        $rules = [
            'title' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'name_addition' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\/\-.\s]+$/'],
            'street' => ['nullable', 'string', 'max:150'],
            'house_number' => ['nullable', 'string', 'max:30'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
        ];

        // Mit Konto bleibt die E-Mail-Adresse unverändert (Anmeldung); sie steht nur ohne Konto zur Bearbeitung.
        if ($person === null || ! $person->user()->exists()) {
            $rules['email'] = [
                'required', 'string', 'email', 'max:254',
                Rule::unique('persons', 'email')->ignore($person?->id),
                Rule::unique('users', 'email'),
            ];
        }

        return $rules;
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'first_name.required' => 'Bitte geben Sie den Vornamen ein.',
            'last_name.required' => 'Bitte geben Sie den Nachnamen ein.',
            'birth_date.required' => 'Bitte geben Sie das Geburtsdatum ein.',
            'birth_date.before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'birth_date.after' => 'Bitte prüfen Sie das Geburtsdatum.',
            'email.required' => 'Bitte geben Sie die E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
            'phone.regex' => 'Bitte geben Sie eine gültige Telefonnummer ein.',
            'country_code.size' => 'Bitte geben Sie das Land als zweistelligen Code an, z. B. DE.',
            'country_code.alpha' => 'Bitte geben Sie das Land als zweistelligen Code an, z. B. DE.',
        ];
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
