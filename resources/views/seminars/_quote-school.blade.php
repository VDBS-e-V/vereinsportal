@php
    use App\Modules\Quotes\Enums\QuoteStatus;
    use App\Modules\Seminars\Enums\SeminarStatus;
    use App\Support\Money;

    $quote = $seminar->quotes()->whereIn('status', [QuoteStatus::Sent->value, QuoteStatus::Accepted->value, QuoteStatus::Declined->value])->first();
    $answerable = $quote !== null && $quote->status === QuoteStatus::Sent && $seminar->status === SeminarStatus::QuoteSent;
@endphp

@if ($quote !== null)
    <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Kostenvoranschlag</h2>

    @error('quote')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    <p><x-vdbs.data.badge :tone="$quote->status->tone()">{{ $quote->status->label() }}</x-vdbs.data.badge>
        Version {{ $quote->version }}, Gesamtbetrag {{ Money::format($quote->total_cents) }}.
        <a href="{{ route('quotes.pdf', $quote) }}">PDF öffnen</a></p>

    @if ($answerable)
        <x-vdbs.forms.form :action="route('school.quotes.accept', [$seminar, $quote])" :summary="false" style="margin-bottom:var(--space-5)">
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Kostenvoranschlag annehmen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>

        <x-vdbs.forms.form :action="route('school.quotes.question', [$seminar, $quote])" :summary="false" style="margin-bottom:var(--space-5)">
            <x-vdbs.forms.textarea name="note" label="Frage oder Änderungswunsch" :required="true" maxlength="2000" />
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary">Nachricht senden</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>

        <x-vdbs.forms.form :action="route('school.quotes.decline', [$seminar, $quote])" :summary="false">
            <x-vdbs.forms.textarea id="decline-note" name="note" label="Grund der Ablehnung (freiwillig)" :optional="true" maxlength="2000" />
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="danger-text">Kostenvoranschlag ablehnen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif
@endif
