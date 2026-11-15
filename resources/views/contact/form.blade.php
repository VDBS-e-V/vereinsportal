@php
    $prefill = \App\Modules\ContactForms\Http\Controllers\ContactFlowController::prefill(auth()->user());
    $steps = ['Thema wählen', 'Ihre Daten'];

    if ($form->isMessage()) {
        $steps[] = 'Ihre Anfrage';
    } elseif ($form->customFields() !== []) {
        $steps[] = 'Weitere Angaben';
    }

    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Kontakt', 'href' => route('portal.contact')], ['label' => $form->name]];
    $lead = filled($form->description) ? $form->description : 'Bitte geben Sie Ihre Daten ein, wir melden uns bei Ihnen.';
    $split = $form->hasRequestStep();
@endphp

<x-layouts.app :title="$form->name" width="medium">
    <x-vdbs.layout.page-header :title="$form->name" :lead="$lead" :breadcrumb="$crumbs" />

    <x-vdbs.navigation.stepper :steps="$steps" :current="2" label="Ablauf der Kontaktaufnahme" />

    @if ($topic)
        <p style="margin-top:var(--space-5)">Ihr Thema: <strong>{{ $topic->title }}</strong> · <a href="{{ route('portal.contact') }}">Thema ändern</a></p>
    @endif

    <x-vdbs.forms.form :action="$split ? route('portal.contact.details', ['form' => $form->slug]) : route('portal.contact.send', ['form' => $form->slug])">
        <x-vdbs.forms.bot-guard />
        <input type="hidden" name="thema" value="{{ $topic?->id }}">

        <x-vdbs.forms.form-section title="Schritt 2: Ihre Daten">
            @include('contact._person', ['form' => $form, 'prefill' => $prefill])
        </x-vdbs.forms.form-section>

        @unless ($split)
            <x-vdbs.forms.checkbox name="consent" :required="true" label="Ich willige ein, dass meine Daten zum Zweck der Kontaktaufnahme gespeichert und verarbeitet werden. Ich kann diese Einwilligung jederzeit widerrufen. Weitere Informationen finden Sie in unserer Datenschutzerklärung (siehe Fußzeile)." />

        {{-- Köder für Programme: Menschen sehen und füllen das Feld nicht aus. --}}
        <div style="position:absolute;left:-10000px" aria-hidden="true">
            <label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label>
        </div>

        @endunless

        <x-slot:actions>
            @if ($split)
                <x-vdbs.actions.button type="submit" icon="arrow-right">Weiter zu Schritt 3</x-vdbs.actions.button>
            @else
                <x-vdbs.actions.button type="submit" icon="send">Absenden</x-vdbs.actions.button>
            @endif
            <x-vdbs.actions.button variant="text" :href="route('portal.contact')">Anderes Thema wählen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
