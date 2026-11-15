<?php

namespace App\Modules\Faq\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Faq\Models\FaqCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Die große FAQ-Seite (/faq): alle Fragen und Antworten der aktiven Kategorien, mit Suche. Gepflegt im Portal unter Geschäftsstelle › FAQ. */
class FaqPageController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $words = array_slice(array_values(array_filter(preg_split('/\s+/u', mb_strtolower($query)) ?: [])), 0, 6);

        $user = $request->user();

        // Je nach Einstellung der Kategorie: öffentlich, nur Angemeldete oder bestimmte Rollen (wer die FAQ pflegt, sieht zur Kontrolle alle sichtbaren Kategorien)
        $categories = FaqCategory::query()->active()->with(['entries' => fn ($entries) => $entries->where('is_active', true)])->orderBy('sort_order')->orderBy('title')->get()
            ->filter(fn (FaqCategory $category): bool => $category->isVisibleTo($user))->values();

        if ($words !== []) {
            $categories->each(fn (FaqCategory $category) => $category->setRelation('entries', $category->entries->filter(function ($entry) use ($words): bool {
                $haystack = mb_strtolower($entry->question.' '.$entry->answer);

                foreach ($words as $word) {
                    if (! str_contains($haystack, $word)) {
                        return false;
                    }
                }

                return true;
            })->values()));
        }

        $categories = $categories->filter(fn (FaqCategory $category): bool => $category->entries->isNotEmpty())->values();

        return view('faq.index', ['categories' => $categories, 'query' => $query, 'total' => $categories->sum(fn (FaqCategory $category): int => $category->entries->count())]);
    }
}
