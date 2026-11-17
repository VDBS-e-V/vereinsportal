@php
    use App\Modules\Seminars\Enums\SeminarStatus;
    $reportStates = [SeminarStatus::Held, SeminarStatus::Reported, SeminarStatus::Completed];
@endphp

@if (in_array($seminar->status, $reportStates, true))
    @can('seminars.manage')
        <section aria-labelledby="closing-heading" style="margin-top:var(--space-8)">
            <h2 id="closing-heading" style="font-size:var(--font-size-h3)">Bericht und Ergebnislink</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Unterlagen der Seminarleitung">{{ $seminar->reported_at ? 'abgegeben am '.$seminar->reported_at->format('d.m.Y') : ($seminar->report_due_at ? 'fällig bis '.$seminar->report_due_at->format('d.m.Y') : 'noch nicht fällig') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Bericht an die Schule">{{ $seminar->report_sent_at ? 'gesendet am '.$seminar->report_sent_at->format('d.m.Y') : 'noch nicht gesendet' }}</x-vdbs.data.detail-item>
                @if ($seminar->results_share_url)<x-vdbs.data.detail-item label="Ergebnislink">{{ $seminar->results_share_url }} (bis {{ $seminar->results_expires_at?->format('d.m.Y') }})</x-vdbs.data.detail-item>@endif
            </x-vdbs.data.detail-list>

            <x-vdbs.forms.form :action="route('seminars.report.send', $seminar)" method="post" :summary="false">
                <x-vdbs.forms.form-grid :columns="2">
                    <x-vdbs.forms.input id="rs-url" name="results_share_url" label="Link zum Ergebnisordner" :optional="true" maxlength="500" placeholder="https://…" :value="old('results_share_url', $seminar->results_share_url)" />
                    <x-vdbs.forms.input id="rs-exp" name="results_expires_at" type="date" label="Link gilt bis" :optional="true" hint="Mindestens ein Jahr nach dem letzten Termin." :value="old('results_expires_at', $seminar->results_expires_at?->toDateString())" />
                </x-vdbs.forms.form-grid>
                <x-vdbs.forms.textarea id="rs-text" name="text" label="Nachricht an die Schule" :optional="true" :rows="3" maxlength="1500" />
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="mail">Bericht an die Schule senden</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        </section>
    @endcan
@endif
