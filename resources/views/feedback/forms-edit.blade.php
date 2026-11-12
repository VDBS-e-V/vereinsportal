@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Feedbackformulare', 'href' => route('feedback-forms.index')], ['label' => $form->audienceLabel()]];
@endphp
<x-layouts.app :title="'Formular: '.$form->audienceLabel()" width="wide">
    <x-vdbs.layout.page-header :title="'Formular: '.$form->audienceLabel()" :lead="'Fassung '.$form->version.'. Speichern erzeugt eine neue Fassung; Antworten zu alten Links behalten ihre Fragen.'" :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('feedback-forms.preview', $form)" icon="eye">Vorschau</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.form :action="route('feedback-forms.update', $form)" method="put">
        <x-vdbs.forms.form-section title="Kopf">
            <x-vdbs.forms.input name="title" label="Titel" :required="true" maxlength="200" :value="$form->title" />
            <x-vdbs.forms.textarea name="intro" label="Einleitung" :optional="true" :rows="3" maxlength="1000" :counter="true" :value="$form->intro" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Fragen">
            <p class="vdbs-hint">Die Zahl in „Platz“ bestimmt die Reihenfolge. Fragen mit „Entfernen“ fallen beim Speichern weg.</p>
            @foreach ($form->questions as $i => $question)
                @php($qid = $question['id'])
                <fieldset style="margin-bottom:var(--space-5);padding:var(--space-4);border:1px solid var(--color-border,#ccc)">
                    <legend>{{ $types[$question['type']] ?? $question['type'] }}</legend>
                    <x-vdbs.forms.form-grid :columns="2">
                        <x-vdbs.forms.input :name="'q['.$qid.'][text]'" :id="'q-'.$qid.'-text'" label="Fragetext" :required="true" maxlength="300" :value="$question['text']" />
                        <x-vdbs.forms.input :name="'q['.$qid.'][hint]'" :id="'q-'.$qid.'-hint'" label="Hinweis" :optional="true" maxlength="300" :value="$question['hint'] ?? ''" />
                        <x-vdbs.forms.input :name="'q['.$qid.'][position]'" :id="'q-'.$qid.'-pos'" type="number" label="Platz" min="1" max="99" :value="$i + 1" />
                    </x-vdbs.forms.form-grid>
                    @if (in_array($question['type'], ['choice', 'multi'], true))
                        <x-vdbs.forms.textarea :name="'q['.$qid.'][options]'" :id="'q-'.$qid.'-opt'" label="Antworten (eine pro Zeile)" :rows="4" :value="implode("\n", $question['options'] ?? [])" />
                    @endif
                    @if ($question['type'] !== 'name')
                        <x-vdbs.forms.checkbox :name="'q['.$qid.'][required]'" :id="'q-'.$qid.'-req'" label="Pflichtfrage" :checked="! empty($question['required'])" />
                    @endif
                    <x-vdbs.forms.checkbox :name="'q['.$qid.'][remove]'" :id="'q-'.$qid.'-rm'" label="Entfernen" />
                </fieldset>
            @endforeach
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Neue Frage hinzufügen">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.select name="new_type" label="Typ" :optional="true" :options="['' => 'keine neue Frage'] + $types" />
                <x-vdbs.forms.input name="new_text" label="Fragetext" :optional="true" maxlength="300" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.textarea name="new_options" label="Antworten bei Auswahlfragen (eine pro Zeile)" :optional="true" :rows="3" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="check">Als neue Fassung speichern</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    <form method="post" action="{{ route('feedback-forms.reset', $form) }}" style="margin-top:var(--space-6)" onsubmit="return confirm('Das Formular auf den Standard zurücksetzen? Es entsteht eine neue Fassung.')">@csrf
        <x-vdbs.actions.button type="submit" variant="secondary" icon="refresh-cw">Auf Standard zurücksetzen</x-vdbs.actions.button>
    </form>
</x-layouts.app>
