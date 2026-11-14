@php
    $editing = $form->exists;
    $title = $editing ? 'Formular bearbeiten' : 'Neues Formular';
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Kontaktformular', 'href' => route('contact-admin.topics.index')], ['label' => 'Formulare', 'href' => route('contact-admin.forms.index')], ['label' => $title]];
    $rows = array_values((array) old('fields', $form->fields ?? []));
    $next = count($rows);
    $rows[$next] = [];
    $nextIndex = $next + 1;
@endphp

<x-layouts.app :title="$title" width="wide">
    <x-vdbs.layout.page-header :title="$title" lead="Legen Sie fest, wer die Eingaben erhält und was abgefragt wird. Vor- und Nachname, E-Mail-Adresse und die Einwilligung fragt jedes Formular immer ab." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="$editing ? route('contact-admin.forms.update', $form) : route('contact-admin.forms.store')" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.form-section title="Formular">
            <x-vdbs.forms.input name="name" label="Name" :required="true" maxlength="120" :value="$form->name" hint="Erscheint als Überschrift der Seite und in der E-Mail." />
            <x-vdbs.forms.textarea name="description" label="Einleitung" :optional="true" :rows="3" maxlength="500" :value="$form->description" hint="Steht unter der Überschrift, z. B. wofür das Formular gedacht ist." />
            <x-vdbs.forms.select name="kind" label="Art" :required="true" :options="\App\Modules\ContactForms\Models\ContactForm::KINDS" :value="$form->kind" hint="„Nur Angaben“ fragt keinen Betreff und keine Nachricht ab, sondern nur die Personendaten und Ihre eigenen Felder." />
            <x-vdbs.forms.checkbox name="is_active" label="Formular ist aktiv" :checked="(bool) old('is_active', $form->is_active)" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Empfänger">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="recipient_email" label="E-Mail-Adresse des Empfängers" type="email" :required="true" :value="$form->recipient_email" hint="Dorthin gehen die Eingaben." />
                <x-vdbs.forms.input name="recipient_label" label="Name des Empfängers" :required="true" maxlength="120" :value="$form->recipient_label" hint="So heißt die Stelle in der E-Mail, z. B. Vorstand." />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.checkbox name="send_copy" label="Der absendenden Person eine Kopie per E-Mail schicken" :checked="(bool) old('send_copy', $form->send_copy)" />
            <x-vdbs.forms.input name="success_text" label="Text nach dem Absenden" :optional="true" maxlength="500" :value="$form->success_text" hint="Leer: „Danke, Ihre Nachricht wurde gesendet.“" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Angaben zur Person">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.select name="ask_phone" label="Telefon" :options="\App\Modules\ContactForms\Models\ContactForm::PERSON_MODES" :value="$form->ask_phone" />
                <x-vdbs.forms.select name="ask_organization" label="Schule oder Organisation" :options="\App\Modules\ContactForms\Models\ContactForm::PERSON_MODES" :value="$form->ask_organization" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Eigene Felder" description="Zusätzliche Fragen im Formular. Leere Zeilen werden ignoriert, die Reihenfolge ist die der Zeilen.">
            <div data-vdbs-repeater data-vdbs-repeater-next="{{ $nextIndex }}">
                <div data-vdbs-repeater-items>
                    @foreach ($rows as $i => $row)
                        <x-contact-admin.field-row :i="$i" :row="$row" />
                    @endforeach
                </div>

                <template data-vdbs-repeater-template>
                    <x-contact-admin.field-row i="__INDEX__" />
                </template>

                <x-vdbs.actions.button type="button" variant="secondary" size="m" class="vdbs-js-only" data-vdbs-repeater-add icon="plus">Weiteres Feld hinzufügen</x-vdbs.actions.button>
            </div>
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('contact-admin.forms.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($editing)
        <div style="display:flex;gap:var(--space-4);flex-wrap:wrap;margin-top:var(--space-5)">
            @unless ($form->is_default)
                <form method="post" action="{{ route('contact-admin.forms.default', $form) }}">@csrf
                    <x-vdbs.actions.button type="submit" variant="secondary" icon="star">Als Standardformular festlegen</x-vdbs.actions.button>
                </form>
                <form method="post" action="{{ route('contact-admin.forms.destroy', $form) }}">@csrf @method('delete')
                    <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Dieses Formular löschen? Themen, die dorthin führen, sind danach verborgen.')">Formular löschen</x-vdbs.actions.button>
                </form>
            @else
                <p><x-vdbs.data.badge tone="info">Standardformular</x-vdbs.data.badge> Es wird für „Mein Thema ist nicht dabei“ und für Rückfragen aus FAQ-Seiten verwendet und lässt sich nicht löschen.</p>
            @endunless
        </div>
    @endif
</x-layouts.app>
