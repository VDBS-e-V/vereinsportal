<?php

namespace App\Modules\Pages\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Pages\Enums\PageKey;
use App\Modules\Pages\Models\Page;

/** Rechtstext-Seiten speichern. Das Protokoll nennt die Seite, nicht den Text. */
final class PageAdministration
{
    public function update(PageKey $key, string $body, User $by): Page
    {
        $page = Page::query()->firstOrNew(['key' => $key->value]);
        $changed = ! $page->exists || $page->body !== $body;

        $page->forceFill(['body' => $body, 'updated_by_user_id' => $by->id])->save();

        if ($changed) {
            AuditLog::record(AuditEvent::PageUpdated, $by, null, ['seite' => $key->title()]);
        }

        return $page;
    }
}
