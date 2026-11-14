<?php

namespace App\Modules\ContactForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ContactForms\Mail\ContactFormMail;
use App\Modules\ContactForms\Models\ContactForm;
use App\Modules\ContactForms\Models\ContactTopic;
use App\Modules\ContactForms\Support\ContactFormFields;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Öffentlicher Ablauf des Kontaktbereichs: 1. Thema wählen (Bereiche, Suche), 2. Angaben zur Person, 3. Anfrage oder weitere Angaben.
 * Ein Thema führt zu einem Formular, zum Standardformular, zu einer FAQ-Seite oder zu einem Link. Gespeichert wird nichts, die Angaben gehen per E-Mail an die Stelle des Formulars.
 */
class ContactFlowController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $area = $request->filled('bereich') ? ContactTopic::query()->active()->where('action', 'category')->find((int) $request->query('bereich')) : null;

        $reachable = fn ($topics) => $topics->filter(fn (ContactTopic $topic): bool => $topic->isReachable())->values();

        $base = fn () => ContactTopic::query()->active()->with(['form', 'faqCategory.entries', 'parent'])->orderBy('sort_order')->orderBy('title');

        $results = null;
        $areas = collect();
        $topics = collect();

        if ($query !== '') {
            // Suche über alle Themen: Titel, Beschreibung und Stichwörter; jedes Wort muss vorkommen
            $words = array_filter(preg_split('/\s+/u', mb_strtolower($query)) ?: []);
            $search = $base()->where('action', '!=', 'category');

            foreach (array_slice($words, 0, 6) as $word) {
                $like = '%'.addcslashes($word, '%_\\').'%';
                $search->where(fn ($inner) => $inner->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('keywords', 'like', $like));
            }

            $results = $reachable($search->limit(60)->get())->filter(fn (ContactTopic $topic): bool => $topic->parent === null || $topic->parent->is_active)->values();
        } elseif ($area !== null) {
            $topics = $reachable($base()->where('parent_id', $area->id)->where('action', '!=', 'category')->get());
        } else {
            $areas = $base()->whereNull('parent_id')->where('action', 'category')->get()
                ->each(fn (ContactTopic $category) => $category->setRelation('children', $reachable($category->children()->active()->with(['form', 'faqCategory.entries'])->get())))
                ->filter(fn (ContactTopic $category): bool => $category->children->isNotEmpty())->values();
            $topics = $reachable($base()->whereNull('parent_id')->where('action', '!=', 'category')->get());
        }

        return view('contact.index', [
            'query' => $query,
            'area' => $area,
            'areas' => $areas,
            'topics' => $topics,
            'results' => $results,
            'standard' => ContactForm::standard(),
        ]);
    }

    /** Ein Thema: führt je nach Art zum Formular, zur FAQ-Seite oder zum Link. */
    public function topic(ContactTopic $topic): View|RedirectResponse
    {
        abort_unless($topic->is_active && ($topic->parent === null || $topic->parent->is_active), 404);

        return match ($topic->action) {
            'category' => redirect()->route('portal.contact', ['bereich' => $topic->id]),
            'link' => $this->redirectToLink($topic),
            'faq' => $this->faq($topic),
            default => $this->redirectToForm($topic),
        };
    }

    public function form(Request $request, ContactForm $form): View
    {
        abort_unless($form->is_active, 404);

        $topic = $request->filled('thema') ? ContactTopic::query()->active()->find((int) $request->query('thema')) : null;

        return view('contact.form', ['form' => $form, 'topic' => $topic]);
    }

    public function send(Request $request, ContactForm $form): RedirectResponse
    {
        abort_unless($form->is_active, 404);

        $person = fn (string $mode, array $rules): array => match ($mode) {
            'required' => ['required', ...$rules],
            'optional' => ['nullable', ...$rules],
            default => ['prohibited'],
        };

        [$answerRules, $answerMessages, $answerAttributes] = ContactFormFields::rules($form);

        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:254'],
            'phone' => $person($form->ask_phone, ['string', 'max:50', 'regex:/^[0-9+()\/ .\-]{3,50}$/']),
            'organization' => $person($form->ask_organization, ['string', 'max:150']),
            'thema' => ['nullable', 'integer'],
            'consent' => ['accepted'],
            'website' => ['nullable'],
        ] + $answerRules;

        if ($form->isMessage()) {
            $rules['subject'] = ['required', 'string', 'max:150'];
            $rules['message'] = ['required', 'string', 'max:5000'];
        }

        $data = $request->validate($rules, [
            'first_name.required' => 'Bitte geben Sie Ihren Vornamen ein.',
            'last_name.required' => 'Bitte geben Sie Ihren Nachnamen ein.',
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'phone.required' => 'Bitte geben Sie Ihre Telefonnummer ein.',
            'phone.regex' => 'Bitte geben Sie eine gültige Telefonnummer ein.',
            'organization.required' => 'Bitte geben Sie Ihre Schule oder Organisation an.',
            'subject.required' => 'Bitte geben Sie einen Betreff ein.',
            'message.required' => 'Bitte schreiben Sie Ihre Nachricht.',
            'message.max' => 'Die Nachricht darf höchstens 5.000 Zeichen lang sein.',
            'consent.accepted' => 'Bitte stimmen Sie der Verarbeitung Ihrer Daten zu.',
        ] + $answerMessages, $answerAttributes);

        $success = filled($form->success_text) ? $form->success_text : 'Danke, Ihre Nachricht wurde gesendet.';

        // Das versteckte Feld füllen nur Programme aus: neutral antworten, nichts senden.
        if (filled($request->input('website'))) {
            return redirect()->route('portal.contact')->with('success', $success);
        }

        $topic = filled($data['thema'] ?? null) ? ContactTopic::query()->active()->find((int) $data['thema']) : null;

        $mail = new ContactFormMail(
            $form->name,
            $form->recipient_label,
            $topic?->title,
            [
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => mb_strtolower(trim($data['email'])),
                'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
                'organization' => filled($data['organization'] ?? null) ? trim($data['organization']) : null,
            ],
            $form->isMessage() ? trim($data['subject']) : null,
            $form->isMessage() ? trim($data['message']) : null,
            ContactFormFields::lines($form, (array) ($data['answers'] ?? [])),
        );

        try {
            Mail::to($form->recipient_email)->send($mail);

            if ($form->send_copy) {
                Mail::to($mail->person['email'])->send(new ContactFormMail($mail->formName, $mail->recipientLabel, $mail->topicTitle, $mail->person, $mail->subjectLine, $mail->body, $mail->answers, true));
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Ihre Nachricht konnte gerade nicht gesendet werden. Bitte versuchen Sie es später erneut.');
        }

        return redirect()->route('portal.contact')->with('success', $success);
    }

    private function faq(ContactTopic $topic): View
    {
        $topic->load(['faqCategory.entries', 'form']);
        $follow = $topic->faq_offer_form ? ($topic->targetForm() ?? ContactForm::standard()) : null;

        $category = $topic->faqCategory !== null && $topic->faqCategory->is_active ? $topic->faqCategory : null;

        return view('contact.faq', ['topic' => $topic, 'follow' => $follow, 'category' => $category]);
    }

    private function redirectToForm(ContactTopic $topic): RedirectResponse
    {
        $form = $topic->targetForm();
        abort_if($form === null, 404);

        return redirect()->route('portal.contact.form', ['form' => $form->slug, 'thema' => $topic->id]);
    }

    private function redirectToLink(ContactTopic $topic): RedirectResponse
    {
        $url = (string) $topic->target_url;

        // Nur eigene Pfade oder https-Adressen
        abort_unless(preg_match('#^(/(?!/)|https://)#', $url) === 1, 404);

        return str_starts_with($url, '/') ? redirect($url) : redirect()->away($url);
    }
}
