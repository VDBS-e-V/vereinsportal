<?php

namespace App\Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: Forms. Eintrag im Verlauf eines Antrags. */
class FormEvent extends Model
{
    public $timestamps = false;

    protected $table = 'form_request_events';

    protected $fillable = ['request_id', 'type', 'actor_user_id', 'actor_label', 'comment', 'occurred_at'];

    public const LABELS = [
        'created' => 'Entwurf angelegt', 'submitted' => 'Eingereicht', 'resubmitted' => 'Erneut eingereicht', 'review' => 'In Prüfung genommen', 'checked' => 'Geprüft',
        'self_checked' => 'Selbstprüfung', 'returned' => 'Rückfrage', 'rejected' => 'Abgelehnt', 'approved' => 'Freigegeben', 'paid' => 'Ausgezahlt',
        'exception' => 'Ausnahme Belegfrist', 'exported' => 'In Sammelliste aufgenommen', 'closed' => 'Abgeschlossen',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function label(): string
    {
        return self::LABELS[$this->type] ?? $this->type;
    }
}
