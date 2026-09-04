<?php

namespace App\Modules\Communication\Actions;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class ActivateEmailTemplateAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    /**
     * @param  array<string, mixed>|null  $deviceInfo
     */
    public function execute(
        int $templateId,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): EmailTemplate {
        return DB::transaction(function () use (
            $templateId,
            $actor,
            $ipAddress,
            $userAgent,
            $deviceInfo,
        ): EmailTemplate {
            $template = EmailTemplate::query()
                ->lockForUpdate()
                ->findOrFail($templateId);

            /*
             * Wiederholtes Aktivieren ist ein No-op.
             * Dadurch erzeugen Doppelklicks weder zusätzliche
             * Zustandsänderungen noch irreführende Audit-Events.
             */
            if ($template->is_active) {
                return $template;
            }

            $occurredAt = now();

            $template->is_active = true;
            $template->save();

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::EMAIL_TEMPLATE_ACTIVATED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: 'email_template',
                subjectId: $template->id,
                oldValues: [
                    'status' => 'inactive',
                ],
                newValues: [
                    'status' => 'active',
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
                occurredAt: $occurredAt,
            );

            return $template;
        });
    }
}
