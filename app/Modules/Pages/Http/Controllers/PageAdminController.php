<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Pages\Enums\PageKey;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\PageAdministration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Pflege der Rechtstext-Seiten (Bereich Geschäftsstelle). Berechtigung pages.manage. */
class PageAdminController extends Controller
{
    public function __construct(private readonly PageAdministration $pages) {}

    public function index(): View
    {
        return view('pages.admin-index', ['pages' => array_map(fn (PageKey $key): Page => Page::for($key), PageKey::cases())]);
    }

    public function edit(string $page): View
    {
        return view('pages.admin-form', ['page' => Page::for(PageKey::from($page))]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        $key = PageKey::from($page);
        $data = $request->validate(['body' => ['required', 'string', 'max:60000']], [
            'body.required' => 'Bitte geben Sie den Text ein.',
            'body.max' => 'Der Text darf höchstens 60.000 Zeichen lang sein.',
        ]);

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $this->pages->update($key, $data['body'], $actor);

        return redirect()->route('pages.admin.edit', $key->value)->with('success', 'Die Seite wurde gespeichert.');
    }
}
