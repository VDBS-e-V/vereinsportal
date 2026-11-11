<?php

namespace App\Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Forms. Eine Position eines Antrags (Betrag in Cent). */
class FormItem extends Model
{
    public $timestamps = false;

    protected $table = 'form_request_items';

    protected $fillable = ['request_id', 'position', 'date', 'kind', 'description', 'quantity', 'unit_cents', 'amount_cents', 'extra'];

    public const KINDS = ['travel' => 'Fahrt', 'lodging' => 'Unterkunft', 'meals' => 'Verpflegung', 'material' => 'Material', 'other' => 'Sonstiges'];

    protected function casts(): array
    {
        return ['date' => 'date', 'extra' => 'array', 'quantity' => 'float', 'unit_cents' => 'integer', 'amount_cents' => 'integer'];
    }

    /** @return BelongsTo<FormApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(FormApplication::class, 'request_id');
    }

    /** @return HasMany<FormFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(FormFile::class, 'item_id');
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }
}
