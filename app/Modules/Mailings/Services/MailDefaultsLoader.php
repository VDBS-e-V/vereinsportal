<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Mailings\Models\MailSnippet;
use App\Modules\Mailings\Models\MailTemplate;
use App\Modules\Mailings\Support\MailDefaults;
use Illuminate\Support\Str;

/** Legt fehlende Vorlagen und Bausteine des Grundbestands an. Vorhandene (nach Name) werden nie verändert. */
final class MailDefaultsLoader
{
    /** @return array{templates: int, snippets: int} Zahl neu angelegter Einträge */
    public function load(): array
    {
        $templates = 0;
        $snippets = 0;

        foreach (MailDefaults::templates() as $row) {
            if (MailTemplate::query()->where('kind', MailTemplate::KIND_GENERAL)->where('name', $row['name'])->exists()) {
                continue;
            }

            MailTemplate::query()->create($row + ['key' => 'vorlage-'.Str::lower(Str::random(12)), 'kind' => MailTemplate::KIND_GENERAL]);
            $templates++;
        }

        foreach (MailDefaults::snippets() as $index => $row) {
            if (MailSnippet::query()->where('name', $row['name'])->exists()) {
                continue;
            }

            MailSnippet::query()->create($row + ['sort' => $index + 1]);
            $snippets++;
        }

        return ['templates' => $templates, 'snippets' => $snippets];
    }
}
