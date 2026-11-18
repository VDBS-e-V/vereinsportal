<?php

namespace App\Modules\Coordinations\Models;

use App\Modules\Partners\Models\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Eine Koordination (z. B. Neukölln). Schulen gehören zu genau einer Koordination; sie bestimmt später den Ordner in der Nextcloud. */
class Coordination extends Model
{
    protected $fillable = ['name', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @return HasMany<Partner, $this> */
    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class, 'coordination_id');
    }

    /**
     * @param  Builder<Coordination>  $query
     * @return Builder<Coordination>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Auswahlliste für Formulare: id => Name (nur aktive, dazu die aktuell gewählte). @return array<int, string> */
    public static function options(?int $include = null): array
    {
        return static::query()->where(fn (Builder $query) => $query->where('active', true)->when($include !== null, fn (Builder $inner) => $inner->orWhere('id', $include)))
            ->ordered()->pluck('name', 'id')->all();
    }
}
