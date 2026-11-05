@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Anträge', 'href' => route('forms.index')], ['label' => $application->number]];
    $items = $application->items->values();
    $deadlineNote = 'Belege aus einem Jahr können bis Ende Februar des Folgejahres eingereicht werden.';
@endphp
<x-layouts.app :title="$application->typeLabel().' '.$application->number" width="wide">
    <x-vdbs.layout.page-header :title="$application->typeLabel()" :lead="'Antrag '.$application->number.' · Stand: '.$application->statusLabel()" :breadcrumb="$crumbs" />

    @if ($application->status === 'returned' && $application->rejection_note)
        <x-vdbs.feedback.alert variant="warning" title="Rückfrage der Geschäftsstelle"><p>{{ $application->rejection_note }}</p></x-vdbs.feedback.alert>
    @endif
    @error('form') <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert> @enderror
    @if (($warnings ?? []) !== [])
        <x-vdbs.feedback.alert variant="warning" title="Bitte prüfen"><ul>@foreach ($warnings as $warning)<li>{{ $warning }}</li>@endforeach</ul></x-vdbs.feedback.alert>
    @endif
    @if ($problems !== [])
        <x-vdbs.feedback.alert variant="info" title="Das fehlt noch zum Einreichen"><ul>@foreach ($problems as $problem)<li>{{ $problem }}</li>@endforeach</ul></x-vdbs.feedback.alert>
    @endif

    <x-vdbs.forms.form :action="route('forms.update', $application)" method="put" :files="true" :summary="false">
        <x-vdbs.forms.form-section title="Anlass">
            <x-vdbs.forms.input name="purpose" :label="$application->type === 'purchase' ? 'Begründung / Zweck' : 'Zweck der Ausgaben'" :required="true" maxlength="300" :value="old('purpose', $application->purpose)" hint="z. B. Material für das Seminar an der Schule" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.select name="seminar_id" label="Seminar (freiwillig)" :optional="true" placeholder="Kein Seminar" :options="$seminars" :value="old('seminar_id', $application->seminar_id)" />
                <x-vdbs.forms.select name="cost_center" label="Kostenstelle (freiwillig)" :optional="true" placeholder="Keine" :options="$costCenters" :value="old('cost_center', $application->cost_center)" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        @include('forms.types.'.$application->type)

        <x-vdbs.forms.form-section title="Erklärung und Absenden">
            <x-vdbs.forms.checkbox name="declaration" :label="$application->type === 'purchase' ? 'Der Bedarf ist begründet und die Angaben sind richtig.' : 'Die Angaben sind richtig, die Belege sind Originalausgaben und ich habe keine Doppelerstattung beantragt.'" :checked="(bool) old('declaration', ($application->data['declaration'] ?? false))" />
            <p><strong>Summe: {{ $application->euro() }}</strong> (wird beim Speichern berechnet)</p>
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" name="action" value="submit" icon="send">Antrag einreichen</x-vdbs.actions.button>
            <x-vdbs.actions.button type="submit" name="action" value="save" variant="secondary" icon="save">Entwurf speichern</x-vdbs.actions.button>
            @if ($application->status === 'draft')
                <x-vdbs.actions.button type="submit" name="action" value="discard" variant="text" icon="trash-2">Entwurf löschen</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
