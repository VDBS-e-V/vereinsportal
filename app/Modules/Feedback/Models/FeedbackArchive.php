<?php

namespace App\Modules\Feedback\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Feedback. Zahlen eines anonymisierten Links (keine Freitexte, keine Namen). */
class FeedbackArchive extends Model
{
    public $timestamps = false;

    protected $fillable = ['link_id', 'seminar_id', 'audience', 'responses_count', 'overall_avg', 'summary', 'archived_at'];

    protected function casts(): array
    {
        return ['summary' => 'array', 'archived_at' => 'datetime', 'responses_count' => 'integer', 'overall_avg' => 'float'];
    }

    /** @return BelongsTo<FeedbackLink, $this> */
    public function link(): BelongsTo
    {
        return $this->belongsTo(FeedbackLink::class, 'link_id');
    }
}
