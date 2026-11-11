<?php

namespace App\Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: Forms. Kostenstelle zur Zuordnung von Anträgen und Seminaren. */
class CostCenter extends Model
{
    protected $fillable = ['code', 'name', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function label(): string
    {
        return $this->code.' – '.$this->name;
    }
}
