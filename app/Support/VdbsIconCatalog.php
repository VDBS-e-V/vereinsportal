<?php

namespace App\Support;

use Illuminate\Support\Collection;
use RuntimeException;

final class VdbsIconCatalog
{
    /**
     * @return Collection<int, non-empty-string>
     */
    public function all(): Collection
    {
        $path = resource_path(
            'views/components/vdbs/icon.blade.php',
        );

        $source = file_get_contents($path);

        if ($source === false) {
            throw new RuntimeException(
                'The VDBS icon component could not be read.',
            );
        }

        preg_match_all(
            "/@case\\('([^']+)'\\)/",
            $source,
            $matches,
        );

        return collect($matches[1])
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @return Collection<int, non-empty-string>
     */
    public function search(?string $query): Collection
    {
        $query = mb_strtolower(
            trim((string) $query),
        );

        if ($query === '') {
            return $this->all();
        }

        return $this->all()
            ->filter(
                fn (string $name): bool => str_contains(
                    mb_strtolower($name),
                    $query,
                )
            )
            ->values();
    }

    public function snippet(
        string $name,
        int $size = 20,
    ): string {
        return sprintf(
            '<x-vdbs.icon name="%s" size="%d" />',
            $name,
            $size,
        );
    }
}
