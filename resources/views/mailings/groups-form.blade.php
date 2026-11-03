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
            <x-vdbs.forms.multi-select name="groups" label="Gruppen" :searchable="true" placeholder="Gruppen wählen oder suchen" search-label="Gruppen filtern" :options="$groupOptions" :value="$selected" hint="Mehrere Gruppen sind möglich. Die Zahl in Klammern nennt die erreichbaren Personen (ohne Abbestellte und Gesperrte). Wählen Sie mindestens eine Gruppe oder eine einzelne Person." />
            <x-vdbs.forms.multi-select name="exclude" label="Außer" :optional="true" :searchable="true" placeholder="Gruppen ausnehmen" search-label="Gruppen filtern" :options="$excludeOptions" :value="$excludedGroups" hint="Wer in einer dieser Gruppen ist, wird nicht angeschrieben, auch wenn die Person zu einer gewählten Gruppe gehört." />
            <div data-people-url="{{ route($base.'.people') }}">
                <x-vdbs.forms.multi-select name="people" label="Einzelne Personen" :optional="true" :searchable="true" placeholder="Person suchen (mindestens zwei Buchstaben)" search-label="Name suchen" :options="$peopleOptions" :value="array_keys($peopleOptions)" hint="Tippen Sie einen Namen, die Treffer erscheinen in der Liste. Direkt gewählte Personen bekommen das Schreiben auch dann, wenn sie keine Sammelmitteilungen wollen, gesperrte Adressen nie." />
            </div>

            @if ($selected !== [] || $chips !== [])
                <p style="margin:0;color:var(--text-muted)">Erreichbar: {{ $total }} Personen. Nicht angeschrieben: {{ $excludedCount }} durch „Außer“, {{ $unsubscribedCount }} Abgemeldete, {{ $suppressedCount }} gesperrte Adressen.</p>
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
            @if ($snippets->isNotEmpty())
                <div data-snippet-picker data-snippets="{{ json_encode($snippets->pluck('body', 'id')->all(), JSON_UNESCAPED_UNICODE) }}" style="display:none">
                    <x-vdbs.forms.multi-select name="snippet_pick" label="Textbausteine einfügen" :optional="true" :searchable="true" placeholder="Bausteine wählen oder suchen" search-label="Bausteine filtern" :options="$snippets->pluck('name', 'id')->all()" :value="[]" hint="Wählen Sie einen oder mehrere Bausteine. „Einfügen“ setzt sie in dieser Reihenfolge an die Cursorposition im Text." />
                    <x-vdbs.actions.button type="button" size="s" variant="secondary" data-snippet-insert="1">Ausgewählte einfügen</x-vdbs.actions.button>
                </div>
            @endif
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

    <section aria-labelledby="attachments-title" style="margin-top:var(--space-6)">
        <h2 id="attachments-title" style="font-size:var(--font-size-h3)">Anhänge</h2>
        @if (! $draft)
            <p style="color:var(--text-muted)">Anhänge können Sie hinzufügen, sobald das Schreiben als Entwurf gespeichert ist.</p>
        @else
            @if ($attachments->isNotEmpty())
                <ul style="list-style:none;margin:0 0 var(--space-4);padding:0">
                    @foreach ($attachments as $attachment)
                        <li style="display:flex;gap:var(--space-3);align-items:center;margin-bottom:var(--space-2)">
                            <a href="{{ route($base.'.attachments.download', [$draft, $attachment]) }}">{{ $attachment->original_name }}</a>
                            <span style="color:var(--text-muted)">{{ number_format($attachment->size / 1024, 0, ',', '.') }} KB</span>
                            <form method="post" action="{{ route($base.'.attachments.destroy', [$draft, $attachment]) }}">@csrf @method('delete')<x-vdbs.actions.button type="submit" size="s" variant="danger-text">Entfernen</x-vdbs.actions.button></form>
                        </li>
                    @endforeach
                </ul>
            @endif
            <form method="post" action="{{ route($base.'.attachments.store', $draft) }}" enctype="multipart/form-data" style="max-width:32rem">
                @csrf
                <x-vdbs.forms.file name="attachment" label="Datei anhängen" accept=".pdf,.jpg,.jpeg,.png" :hint="'PDF, JPEG oder PNG. Alle Anhänge zusammen höchstens '.$attachmentLimitMb.' MB.'" />
                <x-vdbs.actions.button type="submit" variant="secondary" icon="plus">Anhängen</x-vdbs.actions.button>
            </form>
        @endif
    </section>

    @if ($preview)
        <section aria-labelledby="preview-title" style="margin-top:var(--space-8)">
            <h2 id="preview-title" style="font-size:var(--font-size-h3)">Vorschau</h2>
            @if (session('notice'))
                <x-vdbs.feedback.alert variant="warning"><strong>Einladungsfrist:</strong> {{ session('notice') }}</x-vdbs.feedback.alert>
            @endif
            @if (session('hints'))
                <x-vdbs.feedback.alert variant="info">
                    <strong>Hinweise zur Lesbarkeit:</strong>
                    <ul style="margin:var(--space-2) 0 0">
                        @foreach (session('hints') as $hint)
                            <li>{{ $hint }}</li>
                        @endforeach
                    </ul>
                </x-vdbs.feedback.alert>
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
                @foreach ((array) old('exclude', []) as $group)
                    <input type="hidden" name="exclude[]" value="{{ $group }}">
                @endforeach
                @foreach ((array) old('people', []) as $token)
                    <input type="hidden" name="people[]" value="{{ $token }}">
                @endforeach
                @foreach ($fields as $field)
                    <input type="hidden" name="{{ $field }}" value="{{ old($field) }}">
                @endforeach
                <x-vdbs.actions.button type="submit" icon="mail">{{ $sendLabel }}</x-vdbs.actions.button>
            </form>
        </section>
    @endif

</x-layouts.app>
