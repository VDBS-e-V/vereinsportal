<?php

namespace App\Modules\MailBuilder\Services;

use App\Modules\MailBuilder\Models\MailLayout;
use App\Modules\MailBuilder\Support\BlockSchema;
use App\Modules\MailBuilder\Support\MailRenderer;
use App\Modules\MailBuilder\Support\RenderedMail;

/** Der übliche Weg zu einer fertigen Mail: Dokument bereinigen, Design und Bilder laden, rendern. */
final class MailComposer
{
    public function __construct(private readonly MailAssets $assets) {}

    /**
     * @param  array<string, mixed>  $doc
     * @param  array<string, mixed>  $values
     * @param  array{subject?: string, force_dark?: bool, raw?: list<string>, footer_extra?: string, allowed?: list<string>, protected?: list<string>}  $options
     */
    public function render(array $doc, array $values = [], array $options = [], ?MailLayout $layout = null): RenderedMail
    {
        $doc = BlockSchema::sanitize($doc);
        $layout ??= MailLayout::current();

        return MailRenderer::render($doc, $layout->resolved(), $values, $options + ['assets' => $this->assets->urls($doc)]);
    }
}
