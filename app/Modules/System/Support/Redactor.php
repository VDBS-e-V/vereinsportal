<?php

namespace App\Modules\System\Support;

use Illuminate\Support\Str;

/** Entfernt Geheimnisse aus Texten, die die Systemsteuerung anzeigt (Befehlsausgaben, Protokolldatei, Fehlermeldungen). */
final class Redactor
{
    public static function clean(string $text, ?int $limit = null): string
    {
        $text = (string) preg_replace('/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/', 'Bearer ***', $text);
        $text = (string) preg_replace('/\b(password|passwort|passwd|secret|token|api[_-]?key|authorization)(["\']?\s*[=:]\s*["\']?)[^\s"\',&;]+/i', '$1$2***', $text);

        $secrets = array_filter([
            config('app.key'),
            config('database.connections.'.config('database.default').'.password'),
            config('mail.mailers.smtp.password'),
        ], fn ($value) => is_string($value) && strlen($value) >= 6);

        foreach ($secrets as $secret) {
            $text = str_replace($secret, '***', $text);
        }

        return $limit === null ? $text : Str::limit($text, $limit, "\n… (gekürzt)");
    }
}
