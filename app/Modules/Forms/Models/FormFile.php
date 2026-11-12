<?php

namespace App\Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Forms. Beleg zu einem Antrag, im privaten Speicher. */
class FormFile extends Model
{
    public const DISK = 'local';

    public $timestamps = false;

    protected $table = 'form_request_files';

    protected $fillable = ['request_id', 'item_id', 'original_name', 'path', 'mime', 'size', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** @return BelongsTo<FormApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(FormApplication::class, 'request_id');
    }
}
