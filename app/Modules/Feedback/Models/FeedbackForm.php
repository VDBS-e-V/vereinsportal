<?php

namespace App\Modules\Feedback\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Feedback. Ein Rückmeldeformular für eine Zielgruppe (SuS, Lehrkräfte, Teamende) mit Fragen; jede Änderung erzeugt eine neue Version. */
class FeedbackForm extends Model
{
    public const AUDIENCES = ['students' => 'Schülerinnen und Schüler', 'teachers' => 'Lehrkräfte', 'team' => 'Teamende'];

    protected $fillable = ['key', 'audience', 'title', 'intro', 'version', 'questions', 'active', 'updated_by_user_id'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'active' => 'boolean', 'version' => 'integer'];
    }

    /** @return HasMany<FeedbackFormVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(FeedbackFormVersion::class, 'form_id')->orderByDesc('version');
    }

    public function audienceLabel(): string
    {
        return self::AUDIENCES[$this->audience] ?? $this->audience;
    }
}
