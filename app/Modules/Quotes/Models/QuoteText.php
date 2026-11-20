<?php

namespace App\Modules\Quotes\Models;

use Illuminate\Database\Eloquent\Model;

/** Textbaustein für den Kostenvoranschlag (Einleitung, Schluss). */
class QuoteText extends Model
{
    public const KEYS = ['intro' => 'Einleitung', 'outro' => 'Schlusstext'];

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'body'];

    public static function body(string $key): string
    {
        return (string) static::query()->whereKey($key)->value('body');
    }
}
