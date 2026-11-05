<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Modul: Seminars. Ein Dokument zu einem Seminar (privat gespeichert, nie öffentlich verlinkt). Keine Fotos von Teilnehmenden. */
class SeminarDocument extends Model
{
    public const DISK = 'local';

    /** @var array<string, string> Art => Bezeichnung */
    public const KINDS = [
        'contract' => 'Vertrag',
        'quote' => 'Kostenvoranschlag',
        'participants' => 'Teilnehmendenzahlen oder -liste',
        'evaluation' => 'Auswertung',
        'statistics' => 'Statistik',
        'result' => 'Ergebnis',
        'report' => 'Bericht',
        'invoice' => 'Rechnung oder Abrechnung',
        'other' => 'Sonstiges',
    ];

    /** Nach dem Abschluss geschützt: Löschen nur durch den Vorstand mit Begründung. */
    public const PROTECTED_KINDS = ['contract', 'quote', 'invoice'];

    protected $fillable = ['seminar_id', 'kind', 'title', 'original_name', 'path', 'mime', 'size', 'is_snapshot', 'uploaded_by_user_id', 'retention_until'];

    protected function casts(): array
    {
        return ['is_snapshot' => 'boolean', 'size' => 'integer', 'retention_until' => 'date'];
    }

    protected static function booted(): void
    {
        static::deleting(function (SeminarDocument $document): void {
            Storage::disk(self::DISK)->delete($document->path);
        });
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function isProtected(): bool
    {
        return in_array($this->kind, self::PROTECTED_KINDS, true);
    }
}
