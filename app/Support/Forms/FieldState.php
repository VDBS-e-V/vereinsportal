<?php

namespace App\Support\Forms;

use Illuminate\Support\Str;

/**
 * Gemeinsamer Zustand eines Formularfeldes (Paket "forms").
 *
 * Löst Id, Fehlermeldung (explizit oder aus dem Validierungsfehler-Bag), Erfolgsmeldung und
 * aria-describedby auf, damit alle Feld-Komponenten dasselbe Verhalten haben.
 * Die Id-Konvention (field-<name>) gilt auch für die Sprungmarken in der Fehlerübersicht.
 */
final class FieldState
{
    private function __construct(
        public readonly string $name,
        public readonly string $id,
        public readonly ?string $hint,
        public readonly ?string $error,
        public readonly ?string $success,
        public readonly bool $required,
        public readonly bool $optional,
    ) {}

    /**
     * @param  object|null  $errors  ViewErrorBag oder MessageBag (alles mit first())
     */
    public static function make(
        string $name,
        ?string $id = null,
        ?string $hint = null,
        ?string $error = null,
        ?string $success = null,
        bool $required = false,
        bool $optional = false,
        ?object $errors = null,
    ): self {
        $dotted = self::dotted($name);

        if ($error === null && $errors !== null && is_callable([$errors, 'first'])) {
            $first = $errors->first($dotted);
            $error = filled($first) ? $first : null;
        }

        return new self(
            name: $name,
            id: $id ?? self::idFor($name),
            hint: filled($hint) ? $hint : null,
            error: filled($error) ? $error : null,
            success: filled($success) ? $success : null,
            required: $required,
            optional: $optional && ! $required,
        );
    }

    /** "user[email]" -> "user.email", "tags[]" -> "tags" */
    public static function dotted(string $name): string
    {
        return trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    }

    /** Id-Konvention: "field-" plus der Name ohne Sonderzeichen. */
    public static function idFor(string $name): string
    {
        return 'field-'.trim(preg_replace('/[^a-z0-9]+/i', '-', self::dotted($name)), '-');
    }

    public static function optionId(string $name, string|int $value): string
    {
        return self::idFor($name).'-'.Str::slug((string) $value);
    }

    public function hintId(): string
    {
        return $this->id.'-hint';
    }

    public function errorId(): string
    {
        return $this->id.'-error';
    }

    public function successId(): string
    {
        return $this->id.'-success';
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function hasSuccess(): bool
    {
        return ! $this->hasError() && $this->success !== null;
    }

    /** Wert für aria-describedby (Hinweis, Fehler, Erfolg, plus eigene Ids). */
    public function describedBy(string ...$extra): ?string
    {
        $ids = array_filter([
            $this->hint ? $this->hintId() : null,
            $this->hasError() ? $this->errorId() : null,
            $this->hasSuccess() ? $this->successId() : null,
            ...$extra,
        ]);

        return $ids === [] ? null : implode(' ', $ids);
    }

    /** Alter Eingabewert nach einer fehlgeschlagenen Validierung, sonst der Standardwert. */
    public static function oldValue(string $name, mixed $default = null): mixed
    {
        $request = request();

        return $request->hasSession() ? $request->old(self::dotted($name), $default) : $default;
    }

    /** Für Checkbox/Radio: war die Option im alten Input gesetzt? Ohne alten Input gilt der Standardwert. */
    public static function isChecked(string $name, string $value, bool $default): bool
    {
        $request = request();

        if (! $request->hasSession() || ! $request->session()->hasOldInput()) {
            return $default;
        }

        $old = $request->old(self::dotted($name));

        return is_array($old) ? in_array($value, array_map('strval', $old), true) : (string) $old === $value;
    }
}
