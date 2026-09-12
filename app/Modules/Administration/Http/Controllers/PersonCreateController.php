<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PersonCreateController extends Controller
{
    public function __invoke(
        Request $request,
        AdministrationAccess $access,
    ): View {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::PersonsManage,
            ),
            403,
        );

        $possibleMatchIds = collect(
            $request->session()->get(
                'possible_person_match_ids',
                [],
            ),
        )
            ->filter(
                fn (mixed $id): bool => is_numeric($id),
            )
            ->map(
                fn (mixed $id): int => (int) $id,
            )
            ->unique()
            ->values();

        $possibleMatches = $possibleMatchIds->isEmpty()
            ? collect()
            : Person::query()
                ->with('user')
                ->whereKey($possibleMatchIds->all())
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

        $confirmation = $request->session()->get(
            'possible_person_duplicate_confirmation',
        );

        return view('administration.persons.create', [
            'possibleMatches' => $possibleMatches,
            'possibleDuplicateConfirmation' => is_string($confirmation)
                ? $confirmation
                : null,
            'breadcrumbs' => [
                [
                    'label' => 'Verwaltung',
                    'url' => route('administration.home'),
                ],
                [
                    'label' => 'Personen',
                    'url' => route('administration.persons.index'),
                ],
                [
                    'label' => 'Person anlegen',
                    'url' => null,
                ],
            ],
        ]);
    }
}
