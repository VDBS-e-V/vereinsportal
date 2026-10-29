<?php

namespace App\Modules\Membership\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modul: Membership. Ein hochgeladenes Dokument zu einer Mitgliedschaft (Antrag, Mitgliedsnachweis). Die Datei liegt im
 * privaten Speicher (Disk "local") unter einem zufälligen Namen und wird nur über die Mitgliedschaft ausgeliefert.
 */
class MembershipDocument extends Model
{
    public const KINDS = [
        'application' => 'Antrag',
        'record' => 'Mitgliedsnachweis',
        'other' => 'Sonstiges',
    ];

    public $timestamps = false;

    protected $fillable = ['membership_id', 'kind', 'original_name', 'path', 'mime', 'size', 'sha256', 'uploaded_by_user_id', 'uploaded_at'];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime', 'size' => 'integer'];
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? 'Dokument';
    }

    /** @return BelongsTo<Membership, $this> */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
