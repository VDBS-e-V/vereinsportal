<?php

namespace App\Modules\Contracts\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: Contracts. Eine Fassung des Vertragstextes (versioniert; jede Fassung bleibt erhalten). */
class ContractTemplate extends Model
{
    public const KEY = 'service_agreement';

    public $timestamps = false;

    protected $fillable = ['key', 'version', 'title', 'body', 'active', 'created_by_user_id', 'created_at'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'created_at' => 'datetime', 'version' => 'integer'];
    }
}
