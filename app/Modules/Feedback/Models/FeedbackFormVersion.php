<?php

namespace App\Modules\Feedback\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: Feedback. Unveränderliche Fassung eines Formulars, damit alte Antworten ihre Fragen behalten. */
class FeedbackFormVersion extends Model
{
    public $timestamps = false;

    protected $fillable = ['form_id', 'version', 'title', 'intro', 'questions', 'created_at'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'created_at' => 'datetime'];
    }
}
