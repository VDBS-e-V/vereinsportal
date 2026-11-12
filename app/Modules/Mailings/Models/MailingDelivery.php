<?php

namespace App\Modules\Mailings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modul: Mailings. Die Zustellung eines Schreibens an eine Adresse (Momentaufnahme der Empfänger beim Senden).
 * Enthält personenbezogene Daten und wird nach der Aufbewahrungsfrist gelöscht (portal.mailings.delivery_retention_days).
 */
class MailingDelivery extends Model
{
    public const PENDING = 'pending';

    public const SENDING = 'sending';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const SKIPPED = 'skipped';

    protected $fillable = ['mailing_id', 'email', 'first_name', 'last_name', 'fallback', 'group', 'status', 'attempts', 'error', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    /** @return BelongsTo<Mailing, $this> */
    public function mailing(): BelongsTo
    {
        return $this->belongsTo(Mailing::class);
    }
}
