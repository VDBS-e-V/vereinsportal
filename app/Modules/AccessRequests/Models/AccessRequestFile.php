<?php

namespace App\Modules\AccessRequests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: AccessRequests. Ein Nachweis zum Antrag (privater Speicher, wird nach der Entscheidung gelöscht). */
class AccessRequestFile extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['access_request_id', 'original_name', 'path', 'mime', 'size'];

    /** @return BelongsTo<AccessRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(AccessRequest::class, 'access_request_id');
    }
}
