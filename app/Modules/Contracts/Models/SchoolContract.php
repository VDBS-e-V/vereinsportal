<?php

namespace App\Modules\Contracts\Models;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Contracts. Der Vertrag zwischen Verein und Schule für ein Seminar (Fassung als Text festgehalten; unterschrieben wird auf Papier). */
class SchoolContract extends Model
{
    public const DRAFT = 'draft';

    public const SIGNED = 'signed';

    public const CANCELLED = 'cancelled';

    protected $fillable = ['seminar_id', 'template_version', 'title', 'body', 'status', 'signed_on', 'created_by_user_id', 'signed_recorded_by_user_id'];

    protected function casts(): array
    {
        return ['signed_on' => 'date', 'template_version' => 'integer'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::SIGNED => 'Unterschrieben',
            self::CANCELLED => 'Storniert',
            default => 'Entwurf',
        };
    }
}
