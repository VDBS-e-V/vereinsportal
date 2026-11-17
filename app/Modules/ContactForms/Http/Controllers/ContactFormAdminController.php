<?php

namespace App\Modules\ContactForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\ContactForms\Models\ContactForm;
use App\Modules\ContactForms\Support\ContactFormFields;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Verwaltung der Kontaktformulare: Empfänger, Art (Nachricht oder nur Angaben), abgefragte Personendaten und eigene Felder. Berechtigung contact_forms.manage. */
class ContactFormAdminController extends Controller
{
    public function index(): View
    {
        return view('contact-admin.forms-index', ['forms' => ContactForm::query()->withCount('topics')->orderByDesc('is_default')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('contact-admin.form-form', ['form' => new ContactForm(['kind' => 'message', 'ask_phone' => 'no', 'ask_organization' => 'no', 'is_active' => true, 'send_copy' => false, 'fields' => []])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validated($request, null);
        $form = ContactForm::query()->create($attributes + ['is_default' => false]);

        AuditLog::record(AuditEvent::ContactFormSaved, $this->user(), null, ['formular' => $form->name]);

        return redirect()->route('contact-admin.forms.index')->with('success', 'Das Formular „'.$form->name.'“ wurde angelegt.');
    }

    public function edit(ContactForm $form): View
    {
        return view('contact-admin.form-form', ['form' => $form]);
    }

    public function update(Request $request, ContactForm $form): RedirectResponse
    {
        $attributes = $this->validated($request, $form);

        // Das Standardformular bleibt aktiv, sonst fehlt das Ziel für „Mein Thema ist nicht dabei“
        if ($form->is_default) {
            $attributes['is_active'] = true;
        }

        $form->update($attributes);
        AuditLog::record(AuditEvent::ContactFormSaved, $this->user(), null, ['formular' => $form->name]);

        return redirect()->route('contact-admin.forms.index')->with('success', 'Das Formular „'.$form->name.'“ wurde gespeichert.');
    }

    public function makeDefault(ContactForm $form): RedirectResponse
    {
        DB::transaction(function () use ($form): void {
            ContactForm::query()->where('is_default', true)->update(['is_default' => false]);
            $form->update(['is_default' => true, 'is_active' => true]);
        });

        AuditLog::record(AuditEvent::ContactFormSaved, $this->user(), null, ['formular' => $form->name, 'standard' => 'ja']);

        return back()->with('success', '„'.$form->name.'“ ist jetzt das Standard-Kontaktformular.');
    }

    public function destroy(ContactForm $form): RedirectResponse
    {
        if ($form->is_default) {
            return back()->with('error', 'Das Standardformular lässt sich nicht löschen. Legen Sie zuerst ein anderes als Standard fest.');
        }

        $name = $form->name;
        $form->delete();
        AuditLog::record(AuditEvent::ContactFormDeleted, $this->user(), null, ['formular' => $name]);

        return redirect()->route('contact-admin.forms.index')->with('success', 'Das Formular „'.$name.'“ wurde gelöscht. Themen, die dorthin führten, sind verborgen, bis ein neues Ziel gewählt ist.');
    }

    /** Mehrfachauswahl: löschen. Das Standardformular bleibt stehen. */
    public function destroyMany(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:100'], 'ids.*' => ['integer']], ['ids.required' => 'Bitte wählen Sie mindestens ein Formular aus.']);

        $deleted = 0;
        $kept = 0;

        foreach (ContactForm::query()->whereIn('id', $data['ids'])->get() as $form) {
            if ($form->is_default) {
                $kept++;

                continue;
            }

            $name = $form->name;
            $form->delete();
            AuditLog::record(AuditEvent::ContactFormDeleted, $this->user(), null, ['formular' => $name]);
            $deleted++;
        }

        $message = $deleted.' '.($deleted === 1 ? 'Formular wurde' : 'Formulare wurden').' gelöscht.';

        if ($kept > 0) {
            $message .= ' Das Standardformular blieb stehen.';
        }

        return redirect()->route('contact-admin.forms.index')->with('success', $message);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?ContactForm $form): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'kind' => ['required', Rule::in(array_keys(ContactForm::KINDS))],
            'recipient_email' => ['required', 'string', 'email', 'max:254'],
            'recipient_label' => ['required', 'string', 'max:120'],
            'ask_phone' => ['required', Rule::in(array_keys(ContactForm::PERSON_MODES))],
            'ask_organization' => ['required', Rule::in(array_keys(ContactForm::PERSON_MODES))],
            'success_text' => ['nullable', 'string', 'max:500'],
            'fields' => ['nullable', 'array', 'max:40'],
            'fields.*.label' => ['nullable', 'string', 'max:120'],
            'fields.*.type' => ['nullable', 'string', 'max:20'],
            'fields.*.options' => ['nullable', 'string', 'max:3000'],
            'fields.*.hint' => ['nullable', 'string', 'max:200'],
            'fields.*.key' => ['nullable', 'string', 'max:40'],
        ], [
            'name.required' => 'Bitte geben Sie einen Namen ein.',
            'recipient_email.required' => 'Bitte geben Sie die E-Mail-Adresse des Empfängers ein.',
            'recipient_email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'recipient_label.required' => 'Bitte geben Sie an, wie der Empfänger in der E-Mail heißen soll.',
        ]);

        // Das Kästchen „Pflichtfeld“ steht je Zeile in der Anfrage (nicht in den geprüften Daten)
        $fields = ContactFormFields::normalize((array) $request->input('fields', []));

        foreach ($fields as $field) {
            if ($field['type'] === 'select' && $field['options'] === []) {
                throw ValidationException::withMessages(['fields' => 'Bei „'.$field['label'].'“ fehlen die Einträge der Auswahl (einer je Zeile).']);
            }
        }

        if ($data['kind'] === 'data' && $fields === [] && $data['ask_phone'] === 'no' && $data['ask_organization'] === 'no') {
            throw ValidationException::withMessages(['kind' => 'Ein Formular nur für Angaben braucht mindestens ein eigenes Feld oder die Abfrage von Telefon oder Schule.']);
        }

        return [
            'name' => trim($data['name']),
            'slug' => data_get($form, 'slug') ?? $this->slugFor($data['name']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'kind' => $data['kind'],
            'recipient_email' => mb_strtolower(trim($data['recipient_email'])),
            'recipient_label' => trim($data['recipient_label']),
            'ask_phone' => $data['ask_phone'],
            'ask_organization' => $data['ask_organization'],
            'success_text' => filled($data['success_text'] ?? null) ? trim($data['success_text']) : null,
            'fields' => $fields,
            'send_copy' => $request->boolean('send_copy'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function slugFor(string $name): string
    {
        $base = Str::limit(Str::slug($name), 70, '') ?: 'formular';
        $slug = $base;

        for ($n = 2; ContactForm::query()->where('slug', $slug)->exists(); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
