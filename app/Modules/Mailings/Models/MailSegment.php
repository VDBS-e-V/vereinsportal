<?php

namespace App\Modules\Mailings\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modul: Mailings. Ein benannter Verteiler: gespeichert wird die Regel (Gruppen, Ausschlüsse, Filter, Personen), nie die Adressen.
 * Beim Versand wird er neu berechnet.
 */
class MailSegment extends Model
{
    public const AREAS = ['board' => 'Vorstand', 'office' => 'Geschäftsstelle', 'coordination' => 'Koordination'];

    protected $fillable = ['name', 'description', 'definition', 'visible_to', 'created_by_user_id', 'updated_by_user_id'];

    protected function casts(): array
    {
        return ['definition' => 'array', 'visible_to' => 'array'];
    }

    /** @return list<string> */
    public function groups(): array
    {
        return array_values(array_map('strval', (array) ($this->definition['groups'] ?? [])));
    }

    /** @return list<string> */
    public function exclude(): array
    {
        return array_values(array_map('strval', (array) ($this->definition['exclude'] ?? [])));
    }

    /** @return list<string> */
    public function people(): array
    {
        return array_values(array_map('strval', (array) ($this->definition['people'] ?? [])));
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return (array) ($this->definition['filters'] ?? []);
    }
}
