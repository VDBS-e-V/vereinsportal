@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Mitteilungen an Teamende', 'href' => route('mailings.team.index')], ['label' => 'Neue Mitteilung']];
    $preview = session('preview');
    $fields = ['seminar_id', 'audience', 'own_text'];
    $slot = '<p><em>[Hier steht Ihr eigener Textabschnitt]</em></p>';
    $templateBody = str_replace('{eigener_text}', $slot, $template['body']);
    $audienceOptions = [];
    foreach ($audiences as $key => $label) {
        $audienceOptions[$key] = $label.' ('.$counts[$key].')';
    }
@endphp

<x-layouts.app title="Neue Mitteilung" width="wide">
    <x-vdbs.layout.page-header title="Neue Mitteilung an Teamende" lead="Wählen Sie das Seminar und die Empfänger, schreiben Sie Ihren Abschnitt und prüfen Sie die Vorschau." :breadcrumb="$crumbs" />

    <x-vdbs.feedback.alert variant="info">Teamende, die diese Mitteilungen unter Konto › Benachrichtigungen abbestellt haben, bekommen sie nicht. Die Zahlen in Klammern berücksichtigen das. Zur Auswahl „Interesse“ und „im Team“ gehören die Zahlen des gewählten Seminars.</x-vdbs.feedback.alert>

    <x-vdbs.forms.form :action="route('mailings.team.preview')" method="post">
        <x-vdbs.forms.form-section title="Seminar und Empfänger">
            <x-vdbs.forms.select name="seminar_id" label="Seminar" placeholder="Bitte wählen" :required="true" :options="$seminars" :value="$selected" hint="Seminare in Planung, terminiert oder bestätigt" />
            <x-vdbs.forms.select name="audience" label="Empfänger" :required="true" :options="$audienceOptions" value="all" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Ihr Textabschnitt">
            <x-vdbs.forms.textarea name="own_text" label="Eigener Text" :optional="true" maxlength="10000" :rows="8" :rich="true" :value="null" hint="Erscheint an der markierten Stelle der Vorlage. Platzhalter wie {seminar} oder {erster_termin} sind erlaubt." />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Vorlage der Geschäftsstelle">
            <p style="margin:0;color:var(--text-muted)">Diesen Rahmen gibt die Geschäftsstelle vor. Betreff: <strong>{{ $template['subject'] }}</strong></p>
            <div class="vdbs-article__box" style="display:block;margin-top:0">
                <x-vdbs.content.prose>{!! rich_text($templateBody) !!}</x-vdbs.content.prose>
            </div>
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="eye">Vorschau</x-vdbs.actions.button>
            <x-vdbs.actions.button type="submit" variant="secondary" formaction="{{ route('mailings.team.test') }}">Testmail an mich</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('mailings.team.index')">Zurück</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($preview)
        <section aria-labelledby="preview-title" style="margin-top:var(--space-8)">
            <h2 id="preview-title" style="font-size:var(--font-size-h3)">Vorschau</h2>
            <p style="color:var(--text-muted)">So sieht die Nachricht für Sie aus (Name und Seminar eingesetzt).</p>
            <div class="vdbs-article__box" style="display:block">
                <p><strong>Betreff:</strong> {{ $preview['subject'] }}</p>
                <x-vdbs.content.prose>{!! rich_text($preview['html']) !!}</x-vdbs.content.prose>
            </div>

            <form method="post" action="{{ route('mailings.team.store') }}" style="margin-top:var(--space-4)">
                @csrf
                @foreach ($fields as $field)
                    <input type="hidden" name="{{ $field }}" value="{{ old($field) }}">
                @endforeach
                <x-vdbs.actions.button type="submit" icon="mail">Jetzt senden</x-vdbs.actions.button>
            </form>
        </section>
    @endif
</x-layouts.app>
