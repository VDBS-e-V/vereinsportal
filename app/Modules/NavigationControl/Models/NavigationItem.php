<?php

namespace App\Modules\NavigationControl\Models;

use Illuminate\Database\Eloquent\Model;

/** Eine Seite im Menü eines Bereichs: Änderung einer Seite aus dem Katalog (page_key) oder ein eigener Link (url), auch als Unterseite (parent_ref = Verweis auf die übergeordnete Seite: Schlüssel der Katalogseite oder ref des eigenen Links). */
class NavigationItem extends Model
{
    protected $fillable = ['area', 'ref', 'parent_ref', 'page_key', 'label', 'url', 'sort', 'visible'];

    protected function casts(): array
    {
        return ['visible' => 'boolean'];
    }
}
