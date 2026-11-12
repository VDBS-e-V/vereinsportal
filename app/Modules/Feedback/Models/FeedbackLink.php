<?php

namespace App\Modules\Feedback\Models;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

/** Modul: Feedback. Ein Link zu einem Formular für ein Seminar (viele Antworten über einen Link). Der Link ist verschlüsselt gespeichert und darf von der Koordination wieder angezeigt werden. */
class FeedbackLink extends Model
{
    protected $fillable = ['seminar_id', 'form_id', 'form_version', 'audience', 'token_hash', 'token_enc', 'label', 'opens_at', 'closes_at', 'max_responses', 'min_responses', 'closed_at', 'anonymized_at', 'reminded_at', 'created_by_user_id'];

    protected $hidden = ['token_hash', 'token_enc'];

    protected function casts(): array
    {
        return ['opens_at' => 'datetime', 'closes_at' => 'datetime', 'closed_at' => 'datetime', 'anonymized_at' => 'datetime', 'reminded_at' => 'datetime', 'max_responses' => 'integer', 'min_responses' => 'integer'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<FeedbackForm, $this> */
    public function form(): BelongsTo
    {
        return $this->belongsTo(FeedbackForm::class, 'form_id');
    }

    /** @return HasMany<FeedbackResponse, $this> */
    public function responses(): HasMany
    {
        return $this->hasMany(FeedbackResponse::class, 'link_id');
    }

    /** @return HasOne<FeedbackArchive, $this> */
    public function archive(): HasOne
    {
        return $this->hasOne(FeedbackArchive::class, 'link_id');
    }

    public function url(): string
    {
        return route('feedback.show', ['token' => Crypt::decryptString($this->token_enc)]);
    }

    public function audienceLabel(): string
    {
        return FeedbackForm::AUDIENCES[$this->audience] ?? $this->audience;
    }
}
