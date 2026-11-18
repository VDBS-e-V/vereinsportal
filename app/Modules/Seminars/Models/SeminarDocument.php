<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eine Datei zu einem Seminar, privat gespeichert. Wer sie sehen darf, regelt SeminarDocuments. */
class SeminarDocument extends Model
{
    public const DISK = 'local';

    public const SCHOOL_CONTRACT = 'school_contract';

    /** @var array<string, string> Art => Bezeichnung */
    public const KINDS = ['school_contract' => 'Schulvertrag', 'team_contract' => 'Vertrag Teamende', 'report' => 'Ergebnisbericht', 'material' => 'Material', 'other' => 'Sonstiges'];

    protected $fillable = ['seminar_id', 'kind', 'title', 'path', 'original_name', 'mime', 'size', 'uploaded_by_user_id'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }
}
