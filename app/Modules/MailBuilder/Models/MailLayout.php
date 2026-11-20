<?php

namespace App\Modules\MailBuilder\Models;

use App\Modules\MailBuilder\Support\LayoutSettings;
use Illuminate\Database\Eloquent\Model;

/** Modul: MailBuilder. Das Mail-Design (Farben, Schrift, Logo, Fußzeile). Es gibt genau ein Standarddesign; ohne gespeicherten Eintrag gelten die Vorgaben. */
class MailLayout extends Model
{
    protected $fillable = ['name', 'is_default', 'settings', 'updated_by_user_id'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'settings' => 'array'];
    }

    /** Das Standarddesign; ohne Eintrag ein ungespeichertes mit den Vorgaben. */
    public static function current(): self
    {
        return self::query()->where('is_default', true)->first() ?? new self(['name' => 'Standard', 'is_default' => true, 'settings' => LayoutSettings::DEFAULTS]);
    }

    /**
     * Bereinigte Einstellungen, ergänzt um die Adresse des Logos (falls eines gewählt ist).
     *
     * @return array<string, mixed>
     */
    public function resolved(): array
    {
        $settings = LayoutSettings::normalize($this->settings);
        $logo = $settings['logo_asset_id'] === null ? null : MailAsset::query()->find($settings['logo_asset_id']);
        $settings['logo_url'] = $logo?->url();

        return $settings;
    }
}
