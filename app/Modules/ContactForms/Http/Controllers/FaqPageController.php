<?php

namespace App\Modules\ContactForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ContactForms\Models\ContactTopic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Die große FAQ-Seite (/faq): alle Fragen und Antworten der FAQ-Themen, nach Bereichen gegliedert, mit Suche. Gepflegt im Portal unter Kontaktformular › Themen und FAQ. */
class FaqPageController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $words = array_slice(array_values(array_filter(preg_split('/\s+/u', mb_strtolower($query)) ?: [])), 0, 6);

        $topics = ContactTopic::query()->active()->where('action', 'faq')->where('in_faq_page', true)
            ->with(['parent', 'faqs'])->orderBy('sort_order')->orderBy('title')->get()
            ->filter(fn (ContactTopic $topic): bool => $topic->parent === null || $topic->parent->is_active);

        if ($words !== []) {
            $topics->each(fn (ContactTopic $topic) => $topic->setRelation('faqs', $topic->faqs->filter(function ($faq) use ($words): bool {
                $haystack = mb_strtolower($faq->question.' '.$faq->answer);

                foreach ($words as $word) {
                    if (! str_contains($haystack, $word)) {
                        return false;
                    }
                }

                return true;
            })->values()));
        }

        $topics = $topics->filter(fn (ContactTopic $topic): bool => $topic->faqs->isNotEmpty());

        // Gruppen: Bereiche nach Reihenfolge, Themen ohne Bereich zuletzt
        $groups = $topics->groupBy(fn (ContactTopic $topic): int => (int) $topic->parent_id)
            ->map(fn ($group, $areaId) => ['area' => $areaId > 0 ? $group->first()->parent : null, 'topics' => $group->values()])
            ->sortBy(fn (array $group): string => $group['area'] === null ? '99999-' : sprintf('%05d-%s', $group['area']->sort_order, $group['area']->title))
            ->values();

        return view('faq.index', ['groups' => $groups, 'query' => $query, 'total' => $topics->sum(fn (ContactTopic $topic): int => $topic->faqs->count())]);
    }
}
