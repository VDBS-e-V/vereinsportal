<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Rückmeldung der Schule zu einem Seminar. Die Fragen stehen fest im Code; 5 ist die beste Bewertung. */
class SeminarFeedback extends Model
{
    protected $table = 'seminar_feedback';

    /** @var array<string, string> Schlüssel => Frage */
    public const QUESTIONS = [
        'organization' => 'Organisation und Absprachen',
        'team' => 'Das Team vor Ort',
        'content' => 'Inhalt und Methoden',
        'overall' => 'Gesamteindruck',
    ];

    protected $fillable = ['seminar_id', 'user_id', 'ratings', 'comment'];

    protected function casts(): array
    {
        return ['ratings' => 'array'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rating(string $key): ?int
    {
        $value = ((array) $this->ratings)[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }
}
