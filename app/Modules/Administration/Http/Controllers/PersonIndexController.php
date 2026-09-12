<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class PersonIndexController extends Controller
{
    public function __invoke(
        Request $request,
        AdministrationAccess $access,
    ): View {
        $search = mb_substr(
            trim((string) $request->query('q', '')),
            0,
            120,
        );

        $persons = Person::query()
            ->with('user')
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $like = '%'.$search.'%';

                    $query->where(
                        function (Builder $query) use ($like): void {
                            $query
                                ->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('name_addition', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        },
                    );
                },
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $actor = $request->user();
        $canManagePersons = $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::PersonsManage,
            );
        $canManageUserStatus = $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::UserStatusManage,
            );

        return view('administration.persons.index', [
            'persons' => $persons,
            'search' => $search,
            'canManagePersons' => $canManagePersons,
            'canManageUserStatus' => $canManageUserStatus,
            'actorUserId' => $actor instanceof User ? $actor->id : null,
            'breadcrumbs' => [
                [
                    'label' => 'Verwaltung',
                    'url' => route('administration.home'),
                ],
                [
                    'label' => 'Personen',
                    'url' => null,
                ],
            ],
        ]);
    }
}
