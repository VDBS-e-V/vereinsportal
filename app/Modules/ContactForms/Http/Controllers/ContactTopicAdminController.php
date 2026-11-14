<?php

namespace App\Modules\ContactForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\ContactForms\Models\ContactFaq;
use App\Modules\ContactForms\Models\ContactForm;
use App\Modules\ContactForms\Models\ContactTopic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Verwaltung der Themen des öffentlichen Kontaktbereichs, einschließlich der FAQ-Seiten (Fragen und Antworten je Thema). Berechtigung contact_forms.manage. */
class ContactTopicAdminController extends Controller
{
    public function index(): View
    {
        $roots = ContactTopic::query()->whereNull('parent_id')->with(['form', 'faqs', 'children.form', 'children.faqs'])->orderBy('sort_order')->orderBy('title')->get();

        return view('contact-admin.topics-index', ['roots' => $roots, 'standard' => ContactForm::standard()]);
    }

    public function create(Request $request): View
    {
        $topic = new ContactTopic(['action' => $request->query('art', 'form'), 'is_active' => true, 'faq_offer_form' => true, 'in_faq_page' => true, 'sort_order' => 0, 'parent_id' => $request->integer('bereich') ?: null]);

        return view('contact-admin.topic-form', $this->formData($topic));
    }

    public function store(Request $request): RedirectResponse
    {
        [$attributes, $faqs] = $this->validated($request, null);

        $topic = DB::transaction(function () use ($attributes, $faqs): ContactTopic {
            $topic = ContactTopic::query()->create($attributes);
            $this->syncFaqs($topic, $faqs);

            return $topic;
        });

        AuditLog::record(AuditEvent::ContactTopicSaved, $this->user(), null, ['thema' => $topic->title, 'art' => $topic->action]);

        return redirect()->route('contact-admin.topics.index')->with('success', 'Das Thema „'.$topic->title.'“ wurde angelegt.');
    }

    public function edit(ContactTopic $topic): View
    {
        return view('contact-admin.topic-form', $this->formData($topic));
    }

    public function update(Request $request, ContactTopic $topic): RedirectResponse
    {
        [$attributes, $faqs] = $this->validated($request, $topic);

        DB::transaction(function () use ($topic, $attributes, $faqs): void {
            $topic->update($attributes);
            $this->syncFaqs($topic, $faqs);
        });

        AuditLog::record(AuditEvent::ContactTopicSaved, $this->user(), null, ['thema' => $topic->title, 'art' => $topic->action]);

        return redirect()->route('contact-admin.topics.index')->with('success', 'Das Thema „'.$topic->title.'“ wurde gespeichert.');
    }

    public function destroy(ContactTopic $topic): RedirectResponse
    {
        if ($topic->children()->exists()) {
            return back()->with('error', 'Der Bereich „'.$topic->title.'“ enthält noch Themen. Verschieben oder löschen Sie diese zuerst.');
        }

        $title = $topic->title;
        $topic->delete();
        AuditLog::record(AuditEvent::ContactTopicDeleted, $this->user(), null, ['thema' => $title]);

        return redirect()->route('contact-admin.topics.index')->with('success', 'Das Thema „'.$title.'“ wurde gelöscht.');
    }

    /** Mehrfachauswahl: aktivieren, ausblenden oder löschen. Bereiche mit Themen bleiben beim Löschen stehen. */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'aktion' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
        ], ['ids.required' => 'Bitte wählen Sie mindestens ein Thema aus.']);

        $topics = ContactTopic::query()->whereIn('id', $data['ids'])->get();
        $done = 0;
        $kept = 0;

        foreach ($topics as $topic) {
            if ($data['aktion'] === 'delete') {
                if ($topic->children()->exists()) {
                    $kept++;

                    continue;
                }

                $topic->delete();
                AuditLog::record(AuditEvent::ContactTopicDeleted, $this->user(), null, ['thema' => $topic->title]);
            } else {
                $topic->update(['is_active' => $data['aktion'] === 'activate']);
                AuditLog::record(AuditEvent::ContactTopicSaved, $this->user(), null, ['thema' => $topic->title, 'art' => $topic->action]);
            }

            $done++;
        }

        $message = match ($data['aktion']) {
            'delete' => $done.' '.($done === 1 ? 'Thema wurde' : 'Themen wurden').' gelöscht.',
            'activate' => $done.' '.($done === 1 ? 'Thema ist' : 'Themen sind').' jetzt sichtbar.',
            default => $done.' '.($done === 1 ? 'Thema ist' : 'Themen sind').' jetzt ausgeblendet.',
        };

        if ($kept > 0) {
            $message .= ' '.$kept.' '.($kept === 1 ? 'Bereich enthält' : 'Bereiche enthalten').' noch Themen und '.($kept === 1 ? 'blieb' : 'blieben').' stehen.';
        }

        return redirect()->route('contact-admin.topics.index')->with('success', $message);
    }

    /** @return array<string, mixed> */
    private function formData(ContactTopic $topic): array
    {
        return [
            'topic' => $topic,
            'categories' => ContactTopic::query()->where('action', 'category')->when($topic->exists, fn ($query) => $query->whereKeyNot($topic->id))->orderBy('sort_order')->orderBy('title')->pluck('title', 'id')->all(),
            'forms' => ContactForm::query()->orderBy('name')->pluck('name', 'id')->all(),
            'faqs' => $topic->exists ? $topic->faqs()->get(['question', 'answer'])->map(fn ($faq): array => ['question' => $faq->question, 'answer' => $faq->answer])->all() : [],
            'hasChildren' => $topic->exists && $topic->children()->exists(),
        ];
    }

    /** @return array{0: array<string, mixed>, 1: list<array{question: string, answer: string}>} */
    private function validated(Request $request, ?ContactTopic $topic): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:250'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'action' => ['required', Rule::in(array_keys(ContactTopic::ACTIONS))],
            'parent_id' => ['nullable', 'integer', Rule::exists('contact_topics', 'id')->where('action', 'category')],
            'contact_form_id' => ['nullable', 'integer', 'exists:contact_forms,id'],
            'target_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'faqs' => ['nullable', 'array', 'max:50'],
            'faqs.*.question' => ['nullable', 'string', 'max:250'],
            'faqs.*.answer' => ['nullable', 'string', 'max:5000'],
        ], [
            'title.required' => 'Bitte geben Sie eine Bezeichnung ein.',
            'action.required' => 'Bitte wählen Sie, wohin das Thema führt.',
            'parent_id.exists' => 'Bitte wählen Sie einen vorhandenen Bereich.',
        ]);

        $errors = [];
        $action = $data['action'];

        if ($action === 'category' && filled($data['parent_id'] ?? null)) {
            $errors['parent_id'] = 'Ein Bereich steht immer oben und kann nicht in einem anderen Bereich liegen.';
        }

        if ($topic !== null && (int) ($data['parent_id'] ?? 0) === $topic->id) {
            $errors['parent_id'] = 'Ein Thema kann nicht in sich selbst liegen.';
        }

        if ($topic !== null && $action !== 'category' && $topic->children()->exists()) {
            $errors['action'] = 'Dieser Bereich enthält noch Themen. Verschieben oder löschen Sie diese zuerst.';
        }

        if ($action === 'form' && blank($data['contact_form_id'] ?? null)) {
            $errors['contact_form_id'] = 'Bitte wählen Sie das Formular, zu dem das Thema führt.';
        }

        if ($action === 'link') {
            $url = trim((string) ($data['target_url'] ?? ''));

            if (preg_match('#^(/(?!/)|https://)#', $url) !== 1) {
                $errors['target_url'] = 'Bitte geben Sie einen Pfad im Portal (z. B. /zugang-zum-portal) oder eine Adresse mit https:// ein.';
            }
        }

        $faqs = [];

        foreach ((array) ($data['faqs'] ?? []) as $row) {
            $question = trim((string) ($row['question'] ?? ''));
            $answer = trim((string) ($row['answer'] ?? ''));

            if ($question === '' && $answer === '') {
                continue;
            }

            if ($question === '' || $answer === '') {
                $errors['faqs'] = 'Zu jeder Frage gehört eine Antwort und umgekehrt.';

                continue;
            }

            $faqs[] = ['question' => $question, 'answer' => $answer];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [[
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'keywords' => filled($data['keywords'] ?? null) ? trim($data['keywords']) : null,
            'action' => $action,
            'parent_id' => $action === 'category' ? null : ($data['parent_id'] ?? null),
            'contact_form_id' => $action === 'form' || $action === 'faq' ? ($data['contact_form_id'] ?? null) : null,
            'target_url' => $action === 'link' ? trim((string) $data['target_url']) : null,
            'faq_offer_form' => $request->boolean('faq_offer_form'),
            'in_faq_page' => $request->boolean('in_faq_page'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ], $faqs];
    }

    /** @param list<array{question: string, answer: string}> $faqs */
    private function syncFaqs(ContactTopic $topic, array $faqs): void
    {
        ContactFaq::query()->where('contact_topic_id', $topic->id)->delete();

        if ($topic->action !== 'faq') {
            return;
        }

        foreach ($faqs as $index => $faq) {
            $topic->faqs()->create($faq + ['sort_order' => ($index + 1) * 10]);
        }
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
