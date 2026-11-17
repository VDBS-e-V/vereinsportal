@php
    $workflow = app(\App\Modules\Seminars\Services\SeminarWorkflow::class);
    $hasQuote = $seminar->quotes->isNotEmpty();
    $approved = $hasQuote && $workflow->quoteApproved($seminar);
    $basisLabels = ['contract' => 'Vertrag der Schule unterschrieben', 'commitment' => 'Verbindliche Zusage der Schule'];
@endphp

<section aria-labelledby="review-heading" style="margin-top:var(--space-8)">
    <h2 id="review-heading" style="font-size:var(--font-size-h3)">Prüfung und Freigabe</h2>
    <x-vdbs.data.detail-list layout="inline">
        <x-vdbs.data.detail-item label="Kostenvoranschlag">{{ ! $hasQuote ? 'noch keiner' : ($approved ? 'intern freigegeben am '.$seminar->quote_approved_at->format('d.m.Y') : 'noch nicht intern freigegeben') }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Freigabe des Seminars">{{ $seminar->release_basis ? ($basisLabels[$seminar->release_basis] ?? '').($seminar->released_at ? ' (am '.$seminar->released_at->format('d.m.Y').')' : '') : 'noch nicht freigegeben' }}</x-vdbs.data.detail-item>
        @if (filled($seminar->release_reason))<x-vdbs.data.detail-item label="Begründung">{{ $seminar->release_reason }}</x-vdbs.data.detail-item>@endif
        <x-vdbs.data.detail-item label="Vertrag mit der Schule">{{ $seminar->school_contract_signed_at ? 'unterschrieben am '.$seminar->school_contract_signed_at->format('d.m.Y') : 'noch nicht unterschrieben' }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>
    <div class="vdbs-task-filter">
        @can('seminars.quote_approve')
            @if ($hasQuote && ! $approved)
                <form method="post" action="{{ route('seminars.quote.approve', $seminar) }}">@csrf
                    <x-vdbs.actions.button type="submit" icon="check">Kostenvoranschlag intern freigeben</x-vdbs.actions.button>
                </form>
            @endif
        @endcan
        @can('seminars.review')
            <x-vdbs.actions.button :href="route('school-contracts.show', $seminar)" variant="secondary" icon="file-text">Vertrag mit der Schule</x-vdbs.actions.button>
        @endcan
    </div>
</section>
