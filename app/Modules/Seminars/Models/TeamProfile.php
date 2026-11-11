<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\TeamGender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Selbstauskunft von Teamenden für die Teamzusammenstellung: Kompetenzen, Erfahrung, Erfahrung in der Seminarleitung, freiwillige Angabe zum Geschlecht. */
class TeamProfile extends Model
{
    protected $fillable = ['user_id', 'competences', 'experience', 'lead_experience', 'gender'];

    protected function casts(): array
    {
        return ['competences' => 'array', 'lead_experience' => 'boolean', 'gender' => TeamGender::class];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isEmpty(): bool
    {
        return (array) $this->competences === [] && blank($this->experience);
    }
}
