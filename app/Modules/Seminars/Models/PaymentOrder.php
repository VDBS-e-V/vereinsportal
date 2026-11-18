<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Zahlungsauftrag an die Geschäftsstelle (Buchhaltung): entsteht, wenn die Seminarleitung den ausgefüllten Vertrag einer teamenden Person hochlädt. */
class PaymentOrder extends Model
{
    public const OPEN = 'open';

    public const PAID = 'paid';

    public const REJECTED = 'rejected';

    /** @var array<string, string> */
    public const STATUS = ['open' => 'Offen', 'paid' => 'Bezahlt', 'rejected' => 'Abgelehnt'];

    protected $fillable = ['seminar_id', 'user_id', 'seminar_document_id', 'amount_cents', 'status', 'note', 'created_by_user_id', 'decided_by_user_id', 'decided_at'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'decided_at' => 'datetime'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<SeminarDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(SeminarDocument::class, 'seminar_document_id');
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}
