<?php

namespace App\Modules\Feedback\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Feedback. Eine Antwort ohne Kennung: nur Tag der Abgabe, Antworten und bei Teamenden optional ein Name. */
class FeedbackResponse extends Model
{
    public $timestamps = false;

    protected $fillable = ['link_id', 'submitted_on', 'answers', 'respondent_name'];

    protected function casts(): array
    {
        return ['submitted_on' => 'date', 'answers' => 'array'];
    }

    /** @return BelongsTo<FeedbackLink, $this> */
    public function link(): BelongsTo
    {
        return $this->belongsTo(FeedbackLink::class, 'link_id');
    }
}
