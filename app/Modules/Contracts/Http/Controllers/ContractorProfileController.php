<?php

namespace App\Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Contracts\Services\ContractorProfiles;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** „Meine Vertragsdaten“ (Teamende): Steuernummer, Umsatzsteuer-Art und Bankverbindung für künftige Verträge. */
class ContractorProfileController extends Controller
{
    public function __construct(private readonly ContractorProfiles $profiles) {}

    public function edit(): View
    {
        return view('contracts.profile', ['profile' => $this->profiles->for($this->user())]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'tax_number' => ['nullable', 'string', 'max:30'], 'vat_mode' => ['nullable', 'in:19,7,none'], 'bank_name' => ['nullable', 'string', 'max:120'],
            'bic' => ['nullable', 'string', 'max:20'], 'iban' => ['nullable', 'string', 'max:40'], 'account_holder' => ['nullable', 'string', 'max:150'],
        ]);

        try {
            $this->profiles->save($this->user(), $request->all());
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('contractor.edit')->with('success', 'Ihre Vertragsdaten wurden gespeichert.');
    }

    public function destroy(): RedirectResponse
    {
        $this->profiles->delete($this->user());

        return redirect()->route('contractor.edit')->with('success', 'Ihre Vertragsdaten wurden gelöscht.');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
