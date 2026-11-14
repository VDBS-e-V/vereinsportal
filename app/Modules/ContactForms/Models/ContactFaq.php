<?php

namespace App\Modules\ContactForms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eine Frage mit Antwort auf der FAQ-Seite eines Themas. */
class ContactFaq extends Model
{
    protected $fillable = ['contact_topic_id', 'question', 'answer', 'sort_order'];

    /** @return BelongsTo<ContactTopic, $this> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(ContactTopic::class, 'contact_topic_id');
    }
}
