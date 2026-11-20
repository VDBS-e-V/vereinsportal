<?php

namespace App\Modules\MailBuilder\Services;

use App\Modules\Access\Models\User;
use App\Modules\MailBuilder\Models\MailAsset;
use App\Modules\MailBuilder\Models\MailLayout;
use App\Modules\Mailings\Models\MailTemplate;
use DomainException;
use Illuminate\Http\UploadedFile;

/** Bilder für Mails: Hochladen (nur PNG, JPG, GIF; kein SVG) und die Adressen der in einer Mail benutzten Bilder. */
final class MailAssets
{
    public const MAX_KB = 2048;

    public const MAX_WIDTH = 2400;

    private const TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif'];

    /** @throws DomainException */
    public function store(UploadedFile $file, ?User $by, ?string $alt = null): MailAsset
    {
        $mime = (string) $file->getMimeType();

        if (! isset(self::TYPES[$mime])) {
            throw new DomainException('Bitte laden Sie ein Bild als PNG, JPG oder GIF hoch. SVG zeigen viele Mailprogramme nicht an.');
        }

        if ($file->getSize() > self::MAX_KB * 1024) {
            throw new DomainException('Das Bild ist größer als '.(self::MAX_KB / 1024).' MB.');
        }

        $info = @getimagesize($file->getRealPath());

        if ($info === false) {
            throw new DomainException('Die Datei ist kein lesbares Bild.');
        }

        if ($info[0] > self::MAX_WIDTH) {
            throw new DomainException('Das Bild ist breiter als '.self::MAX_WIDTH.' Pixel. Bitte verkleinern Sie es.');
        }

        $path = $file->storeAs('mail-assets', bin2hex(random_bytes(12)).'.'.self::TYPES[$mime], MailAsset::DISK);

        return MailAsset::query()->create([
            'path' => (string) $path,
            'original_name' => mb_substr(basename((string) $file->getClientOriginalName()), 0, 190),
            'mime' => $mime,
            'alt_default' => $alt !== null && trim($alt) !== '' ? mb_substr(trim($alt), 0, 200) : null,
            'width' => $info[0],
            'height' => $info[1],
            'size' => (int) $file->getSize(),
            'uploaded_by_user_id' => $by?->id,
        ]);
    }

    /** Bilder, die als Logo oder in einer gespeicherten Mail benutzt werden. @return list<int> */
    public function usedIds(): array
    {
        $ids = [];
        $logo = MailLayout::current()->settings['logo_asset_id'] ?? null;

        if (is_numeric($logo)) {
            $ids[] = (int) $logo;
        }

        foreach (MailTemplate::query()->whereNotNull('blocks')->pluck('blocks') as $doc) {
            foreach ((is_array($doc) ? $doc : [])['blocks'] ?? [] as $block) {
                if (is_array($block) && ($block['type'] ?? null) === 'image' && is_numeric($block['props']['asset_id'] ?? null)) {
                    $ids[] = (int) $block['props']['asset_id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Adressen der Bilder, die ein Blockdokument benutzt.
     *
     * @param  array{blocks: list<array<string, mixed>>}  $doc
     * @return array<int, string>
     */
    public function urls(array $doc): array
    {
        $ids = [];

        foreach ($doc['blocks'] as $block) {
            if ($block['type'] === 'image' && is_int($block['props']['asset_id'] ?? null)) {
                $ids[] = $block['props']['asset_id'];
            }
        }

        if ($ids === []) {
            return [];
        }

        return MailAsset::query()->whereIn('id', $ids)->get()->mapWithKeys(fn (MailAsset $asset): array => [$asset->id => $asset->url()])->all();
    }
}
