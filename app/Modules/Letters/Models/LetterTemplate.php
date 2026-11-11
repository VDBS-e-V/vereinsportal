<?php

namespace App\Modules\Letters\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: Letters. Eine Word-Vorlage (.docx) mit Platzhaltern wie ${datum}, die das Portal beim Erzeugen eines Schreibens ausfüllt. Die Datei liegt privat. */
class LetterTemplate extends Model
{
    public const DISK = 'local';

    public const AUDIENCES = ['general' => 'Allgemein', 'school' => 'Schulen', 'team' => 'Teamende'];

    protected $fillable = ['name', 'description', 'audience', 'path', 'original_name', 'size', 'variables', 'created_by_user_id', 'updated_by_user_id'];

    protected function casts(): array
    {
        return ['variables' => 'array', 'size' => 'integer'];
    }

    public function audienceLabel(): string
    {
        return self::AUDIENCES[$this->audience] ?? $this->audience;
    }

    /** @return list<string> */
    public function variableNames(): array
    {
        return array_values(array_map('strval', (array) $this->variables));
    }
}
