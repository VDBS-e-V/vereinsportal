<?php

namespace App\Modules\MailBuilder\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\MailBuilder\Support\BlockSchema;
use App\Modules\MailBuilder\Support\SystemMail;
use App\Modules\MailBuilder\Support\SystemMails;
use App\Modules\Mailings\Models\MailTemplate;
use App\Modules\Mailings\Models\MailTemplateVersion;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Änderungen an Systemmails: laden, speichern (mit Prüfung und Versionen), zurücksetzen, wiederherstellen. */
final class SystemMailTemplates
{
    public const KEEP_VERSIONS = 20;

    public function __construct(private readonly MailComposer $composer) {}

    public function stored(string $key): ?MailTemplate
    {
        return MailTemplate::query()->where('system_key', $key)->first();
    }

    /** Darf diese Person die Mail bearbeiten? Sicherheitsmails nur mit dem besonderen Recht. */
    public function canEdit(SystemMail $mail, User $user): bool
    {
        return $user->can('mail_system.manage') && ($mail->security === false || $user->can('mail_system.security'));
    }

    /**
     * Der Stand für den Editor: die gespeicherte Änderung oder der Standardtext.
     *
     * @return array{doc: array<string, mixed>, subject: string, preheader: string, customized: bool, layout_id: ?int}
     */
    public function current(SystemMail $mail): array
    {
        $template = $this->stored($mail->key);

        if ($template !== null && is_array($template->blocks)) {
            $doc = BlockSchema::sanitize($template->blocks);

            return ['doc' => $doc, 'subject' => (string) $template->subject, 'preheader' => (string) ($template->preheader ?? $doc['preheader']), 'customized' => true, 'layout_id' => $template->layout_id];
        }

        $doc = $mail->document();

        return ['doc' => $doc, 'subject' => $mail->subject, 'preheader' => $doc['preheader'], 'customized' => false, 'layout_id' => null];
    }

    /**
     * Prüft ein Dokument gegen die Regeln der Mail (Platzhalter, Pflichtangaben, Inhalt).
     *
     * @param  array<string, mixed>  $doc
     * @return list<array{level: string, message: string}>
     */
    public function check(SystemMail $mail, array $doc, string $subject): array
    {
        $doc = BlockSchema::sanitize($doc);
        $rendered = $this->composer->render($doc, $mail->samples(), ['subject' => $subject, 'raw' => $mail->raw(), 'allowed' => $mail->allowed(), 'protected' => $mail->protected()]);
        $issues = $rendered->issues;

        if (trim($subject) === '') {
            $issues[] = ['level' => 'error', 'message' => 'Bitte einen Betreff angeben.'];
        }

        return $issues;
    }

    /**
     * @param  array<string, mixed>  $doc
     *
     * @throws DomainException
     */
    public function save(SystemMail $mail, array $doc, string $subject, ?string $preheader, User $by): MailTemplate
    {
        if ($this->canEdit($mail, $by) === false) {
            throw new DomainException('Für diese Mail fehlt Ihnen die Berechtigung.');
        }

        $doc = BlockSchema::sanitize($doc);
        $doc['preheader'] = mb_substr(trim((string) $preheader), 0, 200);
        $subject = trim((string) preg_replace('/\s+/u', ' ', $subject));
        $errors = array_values(array_filter($this->check($mail, $doc, $subject), fn (array $issue): bool => $issue['level'] === 'error'));

        if ($errors !== []) {
            throw new DomainException(implode(' ', array_column($errors, 'message')));
        }

        return DB::transaction(function () use ($mail, $doc, $subject, $by): MailTemplate {
            $template = $this->stored($mail->key);

            if ($template === null) {
                $template = MailTemplate::query()->create(['key' => 'system.'.$mail->key, 'kind' => MailTemplate::KIND_SYSTEM, 'name' => $mail->name, 'description' => $mail->description, 'subject' => $subject, 'body' => '', 'system_key' => $mail->key, 'blocks' => $doc, 'preheader' => $doc['preheader'], 'status' => 'published', 'updated_by_user_id' => $by->id]);
            } else {
                $this->keepVersion($template);
                $template->update(['subject' => $subject, 'blocks' => $doc, 'preheader' => $doc['preheader'], 'status' => 'published', 'updated_by_user_id' => $by->id]);
            }

            AuditLog::record(AuditEvent::MailSystemChanged, $by, null, ['mail' => $mail->key, 'aktion' => 'gespeichert']);

            return $template;
        });
    }

    /** Verwirft die Änderung; ab sofort gilt wieder der Standardtext. */
    public function reset(SystemMail $mail, User $by): void
    {
        if ($this->canEdit($mail, $by) === false) {
            throw new DomainException('Für diese Mail fehlt Ihnen die Berechtigung.');
        }

        $template = $this->stored($mail->key);

        if ($template !== null) {
            $template->delete();
            AuditLog::record(AuditEvent::MailSystemChanged, $by, null, ['mail' => $mail->key, 'aktion' => 'zurückgesetzt']);
        }
    }

    /** Stellt einen früheren Stand wieder her; der aktuelle wird selbst zur Version. */
    public function restore(SystemMail $mail, MailTemplateVersion $version, User $by): MailTemplate
    {
        $template = $this->stored($mail->key);

        if ($template === null || $version->mail_template_id !== $template->id || is_array($version->blocks) === false) {
            throw new DomainException('Dieser Stand gehört nicht zu dieser Mail.');
        }

        return $this->save($mail, $version->blocks, (string) $version->subject, $version->preheader, $by);
    }

    private function keepVersion(MailTemplate $template): void
    {
        MailTemplateVersion::query()->create(['mail_template_id' => $template->id, 'name' => $template->name, 'subject' => (string) $template->subject, 'body' => '', 'blocks' => $template->blocks, 'layout_id' => $template->layout_id, 'preheader' => $template->preheader, 'saved_by_user_id' => $template->updated_by_user_id]);

        $old = MailTemplateVersion::query()->where('mail_template_id', $template->id)->orderByDesc('id')->skip(self::KEEP_VERSIONS)->take(1000)->pluck('id');
        MailTemplateVersion::query()->whereIn('id', $old)->delete();
    }

    /** @return array<string, SystemMail> */
    public function all(): array
    {
        return SystemMails::all();
    }
}
