@php
    $applications = \App\Modules\Forms\Models\FormApplication::query()->where('seminar_id', $seminar->id)->where('status', '!=', 'draft')->get();
@endphp

@if ($applications->isNotEmpty())
<section aria-labelledby="forms-sums-heading" style="margin-top:var(--space-8)">
    <h2 id="forms-sums-heading" style="font-size:var(--font-size-h3)">Kostenerstattungen zu diesem Seminar</h2>
    <p>{{ $applications->count() }} {{ $applications->count() === 1 ? 'Antrag' : 'Anträge' }}, zusammen {{ \App\Modules\Seminars\Support\Money::format((int) $applications->sum('total_cents')) }}. Bezahlt: {{ \App\Modules\Seminars\Support\Money::format((int) $applications->where('status', 'paid')->sum('total_cents')) }}. Nur Zahlen, Einzelheiten stehen in der Bearbeitung der Anträge.</p>
    <x-vdbs.actions.button variant="secondary" :href="route('forms-office.index')">Zu den Anträgen</x-vdbs.actions.button>
</section>
@endif
