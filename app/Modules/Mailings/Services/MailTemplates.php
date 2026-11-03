<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\MailTemplate;
use App\Modules\Mailings\Models\MailTemplateVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Mailvorlagen der Geschäftsstelle. Ohne gespeicherte Fassung gilt die eingebaute. Die Vorlage für Seminare enthält {eigener_text}:
 * An dieser Stelle setzt die Koordination ihren eigenen Textabschnitt ein.
 */
final class MailTemplates
{
    public const TEAM_SEMINAR = 'team_seminar';

    /** @return array{subject: string, body: string} */
    public static function builtin(string $key): array
    {
        return match ($key) {
            self::TEAM_SEMINAR => [
                'subject' => 'Seminar {seminar}: Informationen für Teamende',
                'body' => '<p>Hallo {vorname},</p>'
                    .'<p>wir melden uns zum Seminar <strong>{seminar}</strong> an der Schule {schule}.</p>'
                    .'<p><strong>Termine</strong><br>{termine}</p>'
                    .'{eigener_text}'
                    .'<p>Alle Einzelheiten finden Sie im Portal unter „Seminare“: <a href="{link}">{link}</a></p>'
                    .'<p>Herzliche Grüße<br>{absender}<br>{verein}</p>',
            ],
            default => ['subject' => '', 'body' => ''],
        };
    }

    /** Die gültige Vorlage: gespeichert, sonst eingebaut. @return array{subject: string, body: string} */
    public function get(string $key): array
    {
        $saved = MailTemplate::query()->where('key', $key)->first();

        return $saved !== null ? ['subject' => $saved->subject, 'body' => $saved->body] : self::builtin($key);
    }

    public function isCustomized(string $key): bool
    {
        return MailTemplate::query()->where('key', $key)->exists();
    }

    public function save(string $key, string $subject, string $body, User $by): MailTemplate
    {
        return MailTemplate::query()->updateOrCreate(['key' => $key], ['subject' => trim($subject), 'body' => $body, 'updated_by_user_id' => $by->id]);
    }

    /** Vorlagen für freie Schreiben an Gruppen, nach Name. @return Collection<int, MailTemplate> */
    public function general(): Collection
    {
        return MailTemplate::query()->where('kind', MailTemplate::KIND_GENERAL)->orderBy('name')->get();
    }

    public function createGeneral(string $name, ?string $description, string $subject, string $body, User $by): MailTemplate
    {
        return MailTemplate::query()->create(['key' => 'vorlage-'.Str::lower(Str::random(12)), 'kind' => MailTemplate::KIND_GENERAL, 'name' => trim($name), 'description' => filled($description) ? trim((string) $description) : null, 'subject' => trim($subject), 'body' => $body, 'updated_by_user_id' => $by->id]);
    }

    /** Höchstens so viele frühere Stände je Vorlage bleiben erhalten. */
    public const KEEP_VERSIONS = 20;

    public function updateGeneral(MailTemplate $template, string $name, ?string $description, string $subject, string $body, User $by): MailTemplate
    {
        if ($template->subject !== trim($subject) || $template->body !== $body || $template->name !== trim($name)) {
            MailTemplateVersion::query()->create(['mail_template_id' => $template->id, 'name' => $template->name, 'subject' => $template->subject, 'body' => $template->body, 'saved_by_user_id' => $template->updated_by_user_id]);

            $old = MailTemplateVersion::query()->where('mail_template_id', $template->id)->orderByDesc('id')->skip(self::KEEP_VERSIONS)->take(1000)->pluck('id');
            MailTemplateVersion::query()->whereIn('id', $old)->delete();
        }

        $template->update(['name' => trim($name), 'description' => filled($description) ? trim((string) $description) : null, 'subject' => trim($subject), 'body' => $body, 'updated_by_user_id' => $by->id]);

        return $template;
    }

    /** Stellt einen früheren Stand wieder her. Der aktuelle Stand wird dabei selbst zur Version, die Wiederherstellung lässt sich also zurücknehmen. */
    public function restore(MailTemplate $template, MailTemplateVersion $version, User $by): MailTemplate
    {
        return $this->updateGeneral($template, (string) ($version->name ?: $template->name), $template->description, $version->subject, $version->body, $by);
    }

    public function reset(string $key): void
    {
        MailTemplate::query()->where('key', $key)->delete();
    }
}
