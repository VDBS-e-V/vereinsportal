@php
    $editing = $draft !== null;
    $pageTitle = $editing ? 'Entwurf bearbeiten' : 'Neues Schreiben';
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => $area], ['label' => 'Rundschreiben', 'href' => route($base.'.index')], ['label' => $pageTitle]];
    $preview = session('preview');
    $fields = ['subject', 'body', 'meeting_title', 'meeting_date', 'meeting_time', 'meeting_place', 'meeting_link', 'meeting_deadline', 'mandatory', 'send_at', 'draft_id'];
    $draftId = $draft?->id;
    $sendAtSet = filled(old('send_at'));
    $sendLabel = $needsApproval ? 'Zur Freigabe vorlegen' : ($sendAtSet ? 'Für den gewählten Zeitpunkt planen' : 'An '.$total.' Personen senden');
@endphp

<x-layouts.app :title="$pageTitle" width="wide">
    <x-vdbs.layout.page-header :title="$pageTitle" lead="Wählen Sie die Gruppen, schreiben Sie frei oder übernehmen Sie eine Vorlage. Sie können den Entwurf speichern, den Versand planen und erst nach der Vorschau senden. Jede E-Mail-Adresse bekommt die Nachricht nur einmal." :breadcrumb="$crumbs" />

    @if ($editing && filled($prefill['rejection_note'] ?? null))
        <x-vdbs.feedback.alert variant="warning"><strong>Zurückgegeben mit der Begründung:</strong> {{ $prefill['rejection_note'] }}</x-vdbs.feedback.alert>
    @endif

    @if ($templates !== [] && ! $editing)
        <form method="get" action="{{ route($base.'.create') }}" class="vdbs-mailings__template-picker" style="margin-bottom:var(--space-5)">
            <x-vdbs.forms.select name="vorlage" label="Vorlage übernehmen" placeholder="Ohne Vorlage" :options="$templates" :value="$template?->id" hint="Übernimmt Betreff und Text in das Formular. Sie können beides danach frei ändern." />
            <x-vdbs.actions.button type="submit" variant="secondary" size="s">Vorlage laden</x-vdbs.actions.button>
        </form>
    @endif

    <x-vdbs.forms.form :action="route($base.'.preview')" method="post">
        <input type="hidden" name="draft_id" value="{{ $draftId }}">

        <x-vdbs.forms.form-section title="Empfänger">
            <x-vdbs.forms.checkbox-group name="groups" legend="Gruppen" :required="true" :options="$groupOptions" :value="$selected" hint="Mehrere Gruppen sind möglich. Die Zahl in Klammern nennt die erreichbaren Personen (ohne Abbestellte und Gesperrte)." />
            @if ($selected !== [])
                <p style="margin:0;color:var(--text-muted)">Erreichbar: {{ $total }} Personen. Ausgeschlossen: {{ $unsubscribedCount }} Abgemeldete, {{ $suppressedCount }} gesperrte Adressen.</p>
            @endif
            @if ($needsApproval)
                <x-vdbs.feedback.alert variant="info">Ein Schreiben an {{ $total }} Personen braucht die Freigabe des Vorstands. Es wird erst nach der Freigabe gesendet.</x-vdbs.feedback.alert>
            @endif
            @if ($canMandatory)
                <x-vdbs.forms.checkbox name="mandatory" label="Pflichtmitteilung (nicht abbestellbar)" :checked="$mandatoryChecked" hint="Nur für Vereinspost wie die Einladung zur Mitgliederversammlung. Die Mail enthält dann keinen Abmeldelink. Gesperrte Adressen bekommen sie trotzdem nicht." />
            @endif
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Schreiben">
            <x-vdbs.forms.input name="subject" label="Betreff" :required="true" maxlength="150" :value="$prefill['subject'] ?? null" hint="Platzhalter sind erlaubt, zum Beispiel: Einladung zur {versammlung} am {datum}" />
            <x-vdbs.forms.textarea name="body" label="Text" :required="true" maxlength="20000" :rows="14" :rich="true" :value="$prefill['body'] ?? null" />
            <x-mailings.placeholders :placeholders="$placeholders" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Angaben zu Termin oder Versammlung (optional)">
            <p style="margin:0">Diese Angaben füllen die Platzhalter {versammlung}, {termin}, {datum}, {uhrzeit}, {ort}, {link} und {frist}. Wer einen davon im Text nutzt, muss die zugehörige Angabe machen.</p>
            <x-vdbs.forms.input name="meeting_title" label="Titel" :optional="true" maxlength="150" :value="$prefill['meeting_title'] ?? null" hint="z. B. Mitgliederversammlung 2026" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="meeting_date" type="date" label="Datum" :optional="true" :value="$prefill['meeting_date'] ?? null" />
                <x-vdbs.forms.input name="meeting_time" type="time" label="Uhrzeit" :optional="true" width="10" :value="$prefill['meeting_time'] ?? null" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.input name="meeting_place" label="Ort" :optional="true" maxlength="200" :value="$prefill['meeting_place'] ?? null" />
            <x-vdbs.forms.input name="meeting_link" type="url" label="Link (Online-Teilnahme oder Unterlagen)" :optional="true" maxlength="300" :value="$prefill['meeting_link'] ?? null" hint="Mit https:// beginnen" />
            <x-vdbs.forms.input name="meeting_deadline" type="date" label="Frist (Anmeldung oder Anträge)" :optional="true" :value="$prefill['meeting_deadline'] ?? null" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Versand planen (optional)">
            <x-vdbs.forms.input name="send_at" type="datetime-local" label="Senden am" :optional="true" :value="$prefill['send_at'] ?? null" hint="Leer lassen: sofort senden. Mit Zeitpunkt startet der Planer den Versand dann. Die Empfänger werden erst zu diesem Zeitpunkt bestimmt." />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="eye">Vorschau</x-vdbs.actions.button>
            <x-vdbs.actions.button type="submit" variant="secondary" formaction="{{ route($base.'.draft') }}">Als Entwurf speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button type="submit" variant="secondary" formaction="{{ route($base.'.test') }}">Testmail an mich</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route($base.'.index')">Zurück</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($preview)
        <section aria-labelledby="preview-title" style="margin-top:var(--space-8)">
            <h2 id="preview-title" style="font-size:var(--font-size-h3)">Vorschau</h2>
            @if (session('notice'))
                <x-vdbs.feedback.alert variant="warning"><strong>Einladungsfrist:</strong> {{ session('notice') }}</x-vdbs.feedback.alert>
            @endif
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
                <x-vdbs.actions.button type="submit" icon="mail">{{ $sendLabel }}</x-vdbs.actions.button>
            </form>
        </section>
    @endif
</x-layouts.app>
