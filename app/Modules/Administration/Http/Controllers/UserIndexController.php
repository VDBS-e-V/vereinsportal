<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Support\UserStatusPresentation;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class UserIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search = mb_substr(
            trim((string) $request->query('q', '')),
            0,
            120,
        );

        $requestedStatus = (string) $request->query(
            'status',
            '',
        );
        $status = UserStatus::tryFrom($requestedStatus);

        $users = User::query()
            ->with('person')
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $like = '%'.$search.'%';

                    $query->where(
                        function (Builder $query) use ($like): void {
                            $query
                                ->where('email', 'like', $like)
                                ->orWhereHas(
                                    'person',
                                    function (Builder $personQuery) use ($like): void {
                                        $personQuery
                                            ->where('first_name', 'like', $like)
                                            ->orWhere('last_name', 'like', $like)
                                            ->orWhere('email', 'like', $like);
                                    },
                                );
                        },
                    );
                },
            )
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where(
                    'status',
                    $status->value,
                ),
            )
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $statusOptions = collect(UserStatus::cases())
            ->mapWithKeys(
                fn (UserStatus $option): array => [
                    $option->value => UserStatusPresentation::label($option),
                ],
            )
            ->all();

        return view('administration.users.index', [
            'users' => $users,
            'search' => $search,
            'status' => $status->value ?? '',
            'statusOptions' => $statusOptions,
            'breadcrumbs' => [
                [
                    'label' => 'Verwaltung',
                    'url' => route('administration.home'),
                ],
                [
                    'label' => 'Benutzer',
                    'url' => null,
                ],
            ],
        ]);
    }
}
