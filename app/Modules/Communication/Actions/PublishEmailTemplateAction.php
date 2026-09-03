<?php

namespace App\Modules\Communication\Actions;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Exceptions\EmailTemplateCannotBePublished;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Communication\Services\EmailTemplateHtmlSanitizer;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class PublishEmailTemplateAction
{
    public function __construct(
        private readonly EmailTemplateHtmlSanitizer $sanitizer,
        private readonly AuditWriter $auditWriter,
    ) {
    }

    /**
     * @param array<string, mixed>|null $deviceInfo
     */
    public function execute(
        int $templateId,
        User $publisher,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): EmailTemplateVersion {
        return DB::transaction(function () use (
            $templateId,
            $publisher,
            $ipAddress,
            $userAgent,
            $deviceInfo,
        ): EmailTemplateVersion {
            $template = EmailTemplate::query()
                ->lockForUpdate()
                ->findOrFail($templateId);

            /*
             * Auch die Placeholder-Metadaten werden während
             * der Veröffentlichung stabil gehalten.
             */
            $placeholders = $template
                ->placeholders()
                ->lockForUpdate()
                ->get();

            $subject = trim(
                (string) $template->draft_subject
            );

            $html = (string) $template->draft_html;

            if ($subject === '') {
                throw EmailTemplateCannotBePublished::missingSubject();
            }

            if (trim($html) === '') {
                throw EmailTemplateCannotBePublished::missingHtml();
            }

            $activePlaceholders = $placeholders
                ->where('is_active', true);

            $allowedKeys = $activePlaceholders
                ->pluck('key')
                ->all();

            /*
             * Zuerst auf dem unveränderten Entwurf prüfen.
             *
             * Dadurch kann ein unbekannter Placeholder nicht
             * dadurch "verschwinden", dass er z. B. innerhalb
             * eines vom Sanitizer entfernten <script>-Blocks lag.
             */
            $rawUsedKeys = $this->extractPlaceholderKeys(
                $subject . "\n" . $html
            );

            foreach ($rawUsedKeys as $key) {
                if (! in_array($key, $allowedKeys, true)) {
                    throw EmailTemplateCannotBePublished::unknownPlaceholder(
                        $key
                    );
                }
            }

            $sanitizedHtml = $this->sanitizer->sanitize(
                $html
            );

            if (trim($sanitizedHtml) === '') {
                throw EmailTemplateCannotBePublished::missingHtml();
            }

            /*
             * Pflichtplaceholder nach dem Sanitizing erneut
             * prüfen. Entscheidend ist die tatsächlich
             * veröffentlichte Version.
             */
            $publishedUsedKeys = $this->extractPlaceholderKeys(
                $subject . "\n" . $sanitizedHtml
            );

            $requiredKeys = $activePlaceholders
                ->where('is_required', true)
                ->pluck('key')
                ->all();

            foreach ($requiredKeys as $key) {
                if (! in_array(
                    $key,
                    $publishedUsedKeys,
                    true,
                )) {
                    throw EmailTemplateCannotBePublished::missingRequiredPlaceholder(
                        $key
                    );
                }
            }

            /*
             * Die Template-Zeile ist FOR UPDATE gesperrt.
             * Damit werden zwei Veröffentlichungen desselben
             * Templates serialisiert.
             *
             * Der vorhandene Unique-Constraint
             * (email_template_id, version) bleibt zusätzlich
             * letzte Schutzschicht.
             */
            $latestVersion = $template
                ->versions()
                ->max('version');

            $nextVersion = ((int) $latestVersion) + 1;

            $publishedAt = now();

            $version = $template
                ->versions()
                ->create([
                    'version' => $nextVersion,
                    'subject' => $subject,
                    'html' => $sanitizedHtml,
                    'published_by_user_id' => $publisher->id,
                    'published_at' => $publishedAt,
                ]);

            /*
             * Pflicht-Audit innerhalb derselben Transaktion.
             * Scheitert der Audit-Write, wird auch die neue
             * Template-Version zurückgerollt.
             */
            $this->auditWriter->write(
                eventKey: AuditEventCatalog::EMAIL_TEMPLATE_PUBLISHED,
                actorType: AuditActorType::User,
                actorUserId: $publisher->id,
                subjectType: 'email_template_version',
                subjectId: $version->id,
                newValues: [
                    'version' => $nextVersion,
                    'key' => $template->key,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
                occurredAt: $publishedAt,
            );

            return $version;
        });
    }

    /**
     * @return list<string>
     */
    private function extractPlaceholderKeys(
        string $content,
    ): array {
        preg_match_all(
            '/{{\s*([A-Za-z0-9_.-]+)\s*}}/',
            $content,
            $matches,
        );

        return array_values(
            array_unique(
                $matches[1] ?? []
            )
        );
    }
}