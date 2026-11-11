<?php

namespace App\Modules\Contacts\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: Contacts. Ein Import einer Liste (nur Zahlen und Dateiname, nie der Inhalt). */
class ImportBatch extends Model
{
    protected $fillable = ['kind', 'file_name', 'created_by_user_id', 'rows_total', 'rows_new', 'rows_updated', 'rows_skipped', 'consent_confirmed_at'];

    protected function casts(): array
    {
        return ['consent_confirmed_at' => 'datetime'];
    }
}
