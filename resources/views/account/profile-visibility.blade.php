@php
    $options = collect(\App\Modules\Access\Enums\ProfileVisibility::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();
@endphp

<x-account.shell title="Sichtbarkeit" current="profile-visibility" lead="Legen Sie für jede Angabe fest, wer sie sehen darf. Ohne Freigabe sieht nur Sie selbst die Angabe.">
    <x-vdbs.forms.form :action="route('account.profile.visibility.update')" method="put">
        <x-vdbs.forms.form-section title="Wer sieht was?">
            @foreach ($fields as $key => $label)
                <x-vdbs.forms.select :id="'visibility-'.$key" :name="'visibility['.$key.']'" :label="$label" :options="$options" :value="old('visibility.'.$key, $profile->visibilityOf($key)->value)" :required="true" />
            @endforeach
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Einwilligung">
            <p>Mit einer Freigabe zeigen wir die gewählten Angaben den genannten Personen. E-Mail-Adresse und Telefon geben Sie nur frei, wenn Sie dort ansprechbar sein möchten. Sie können jede Freigabe jederzeit zurücknehmen; sie wirkt sofort.</p>
            <x-vdbs.forms.checkbox name="consent" label="Ich bin damit einverstanden, dass die gewählten Angaben für die gewählten Personen sichtbar sind." :checked="$profile->consent_at !== null" />
            @if ($profile->consent_at)
                <p style="color:var(--text-muted)">Einwilligung erteilt am {{ $profile->consent_at->format('d.m.Y') }}.</p>
            @endif
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('account.profile')">Vorschau ansehen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-account.shell>
