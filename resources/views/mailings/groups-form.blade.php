@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => $area], ['label' => 'Rundschreiben', 'href' => route($base.'.index')], ['label' => 'Neues Schreiben']];
    $preview = session('preview');
    $fields = ['subject', 'body', 'meeting_title', 'meeting_date', 'meeting_time', 'meeting_place', 'meeting_link', 'meeting_deadline'];
    $prefillSubject = $template?->subject;
@endphp

<x-layouts.app title="Neues Schreiben" width="wide">
    <x-vdbs.layout.page-header title="Neues Schreiben" lead="Wählen Sie die Gruppen, schreiben Sie frei oder übernehmen Sie eine Vorlage. Erst die Vorschau, dann das Senden. Jede E-Mail-Adresse bekommt die Nachricht nur einmal." :breadcrumb="$crumbs" />

    @if ($templates !== [])
        <form method="get" action="{{ route($base.'.create') }}" class="vdbs-mailings__template-picker" style="margin-bottom:var(--space-5)">
            <x-vdbs.forms.select name="vorlage" label="Vorlage übernehmen" placeholder="Ohne Vorlage" :options="$templates" :value="$template?->id" hint="Übernimmt Betreff und Text in das Formular. Sie können beides danach frei ändern." />
            <x-vdbs.actions.button type="submit" variant="secondary" size="s">Vorlage laden</x-vdbs.actions.button>
        </form>
    @endif

    <x-vdbs.forms.form :action="route($base.'.preview')" method="post">
        <x-vdbs.forms.form-section title="Empfänger">
            <x-vdbs.forms.checkbox-group name="groups" legend="Gruppen" :required="true" :options="$groupOptions" :value="$selected" hint="Mehrere Gruppen sind möglich. Die Zahl in Klammern nennt die erreichbaren Personen (ohne Abbestellte)." />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Schreiben">
            <x-vdbs.forms.input name="subject" label="Betreff" :required="true" maxlength="150" :value="$prefillSubject" hint="Platzhalter sind erlaubt, zum Beispiel: Einladung zur {versammlung} am {datum}" />
            <x-vdbs.forms.textarea name="body" label="Text" :required="true" maxlength="20000" :rows="14" :rich="true" :value="$prefillBody" />
            <x-mailings.placeholders :placeholders="$placeholders" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Angaben zu Termin oder Versammlung (optional)">
            <p style="margin:0">Diese Angaben füllen die Platzhalter {versammlung}, {termin}, {datum}, {uhrzeit}, {ort}, {link} und {frist}. Wer einen davon im Text nutzt, muss die zugehörige Angabe machen.</p>
            <x-vdbs.forms.input name="meeting_title" label="Titel" :optional="true" maxlength="150" hint="z. B. Mitgliederversammlung 2026" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="meeting_date" type="date" label="Datum" :optional="true" />
                <x-vdbs.forms.input name="meeting_time" type="time" label="Uhrzeit" :optional="true" width="10" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.input name="meeting_place" label="Ort" :optional="true" maxlength="200" />
            <x-vdbs.forms.input name="meeting_link" type="url" label="Link (Online-Teilnahme oder Unterlagen)" :optional="true" maxlength="300" hint="Mit https:// beginnen" />
            <x-vdbs.forms.input name="meeting_deadline" type="date" label="Frist (Anmeldung oder Anträge)" :optional="true" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="eye">Vorschau</x-vdbs.actions.button>
            <x-vdbs.actions.button type="submit" variant="secondary" formaction="{{ route($base.'.test') }}">Testmail an mich</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route($base.'.index')">Zurück</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($preview)
        <section aria-labelledby="preview-title" style="margin-top:var(--space-8)">
            <h2 id="preview-title" style="font-size:var(--font-size-h3)">Vorschau</h2>
            <p style="color:var(--text-muted)">So sieht die Nachricht für Sie aus (Name und Angaben eingesetzt). Das Schreiben geht an {{ $total }} Personen.</p>
            <div class="vdbs-article__box" style="display:block">
                <p><strong>Betreff:</strong> {{ $preview['subject'] }}</p>
                <x-vdbs.content.prose>{!! rich_text($preview['html']) !!}</x-vdbs.content.prose>
            </div>

            <form method="post" action="{{ route($base.'.store') }}" style="margin-top:var(--space-4)">
                @csrf
                @foreach ((array) old('groups', []) as $group)
                    <input type="hidden" name="groups[]" value="{{ $group }}">
                @endforeach
                @foreach ($fields as $field)
                    <input type="hidden" name="{{ $field }}" value="{{ old($field) }}">
                @endforeach
                <x-vdbs.actions.button type="submit" icon="mail">An {{ $total }} Personen senden</x-vdbs.actions.button>
            </form>
        </section>
    @endif
</x-layouts.app>
