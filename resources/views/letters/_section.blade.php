@php
    $templates = \App\Modules\Letters\Models\LetterTemplate::query()->orderBy('name')->get();
    $recipients = app(\App\Modules\Letters\Services\LetterGenerator::class)->recipients($seminar);
@endphp

<section aria-labelledby="letters-heading" style="margin-top:var(--space-8)">
    <h2 id="letters-heading" style="font-size:var(--font-size-h3)">Schreiben</h2>

    @if ($templates->isEmpty())
        <p>Es gibt noch keine Briefvorlagen. <a href="{{ route('letters.index') }}">Zu den Briefvorlagen</a></p>
    @else
        <p>Erzeugt Word-Dateien aus einer Vorlage mit den Angaben zu diesem Seminar. Es wird nichts gespeichert oder versendet.</p>
        <x-vdbs.forms.form :action="route('letters.generate', $seminar)" method="post" :summary="false">
            <x-vdbs.forms.select name="template_id" label="Vorlage" :required="true" placeholder="Vorlage wählen" :options="$templates->mapWithKeys(fn ($template) => [$template->id => $template->name.' ('.$template->audienceLabel().')'])->all()" :value="old('template_id')" />
            <x-vdbs.forms.radio-group name="to" legend="Empfänger" :options="['school' => 'An die Schule', 'team' => 'An Personen aus dem bestätigten Team (je Person eine Datei)']" :value="old('to', 'school')" />
            @if ($recipients !== [])
                <x-vdbs.forms.multi-select name="applications" label="Personen (nur bei „Team“)" :optional="true" :searchable="true" placeholder="Alle bestätigten Personen" search-label="Personen filtern" :options="collect($recipients)->mapWithKeys(fn ($application) => [$application->id => $application->applicantName()])->all()" :value="[]" hint="Ohne Auswahl bekommen alle bestätigten Personen ein Schreiben. Mehrere Dateien kommen als ZIP." />
            @endif
            <x-vdbs.forms.input name="subject" label="Betreff" :optional="true" maxlength="200" :value="old('subject')" hint="Für den Platzhalter ${betreff}" />
            <x-vdbs.forms.textarea name="freitext" label="Freier Text" :optional="true" maxlength="6000" :rows="5" :value="old('freitext')" hint="Für den Platzhalter ${freitext}; Zeilenumbrüche bleiben erhalten." />
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="download">Schreiben erzeugen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif
</section>
