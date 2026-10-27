<?php

namespace App\Modules\DesignLibrary\Http;

use App\Http\Controllers\Controller;
use App\Modules\DesignLibrary\Enums\LibraryCategory;
use App\Modules\DesignLibrary\Support\PlaceholderImages;
use App\Modules\DesignLibrary\Support\Registry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class DesignLibraryController extends Controller
{
    public function index(): View
    {
        return view('design-library.index', [
            'categories' => LibraryCategory::cases(),
            'grouped' => Registry::grouped(),
        ]);
    }

    public function show(string $slug): View
    {
        $entry = Registry::find($slug);

        abort_if($entry === null, 404);

        return view('design-library.show', ['entry' => $entry]);
    }

    /** Bestand der Platzhalterbilder als JSON (Arten, Formate, vorhandene Dateien). */
    public function placeholders(): JsonResponse
    {
        return response()->json(['data' => PlaceholderImages::make()->overview()]);
    }
}
