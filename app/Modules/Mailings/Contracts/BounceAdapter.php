<?php

namespace App\Modules\Mailings\Contracts;

/** Liest die Rückmeldungen eines Versanddienstes (Webhook-Inhalt) in ein einheitliches Format. Für jeden Dienst gibt es eine eigene Umsetzung. */
interface BounceAdapter
{
    /**
     * @return list<array{type: string, email: string}> type: hard_bounce, soft_bounce oder complaint
     */
    public function parse(string $body): array;
}
