<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

final class WebContentLibrary
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        return collect(
            config('web_content_library.items', [])
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function search(
        ?string $query = null,
        ?string $category = null,
        ?string $status = null,
    ): Collection {
        $query = mb_strtolower(trim((string) $query));

        return $this->all()
            ->filter(function (array $item) use (
                $query,
                $category,
                $status,
            ): bool {
                if (
                    $category !== null
                    && $category !== ''
                    && ($item['category'] ?? null) !== $category
                ) {
                    return false;
                }

                if (
                    $status !== null
                    && $status !== ''
                    && ($item['status'] ?? null) !== $status
                ) {
                    return false;
                }

                if ($query === '') {
                    return true;
                }

                $haystack = mb_strtolower(
                    implode(' ', [
                        (string) ($item['id'] ?? ''),
                        (string) ($item['name'] ?? ''),
                        (string) ($item['description'] ?? ''),
                        (string) ($item['source'] ?? ''),
                        implode(' ', $item['tags'] ?? []),
                        implode(' ', $item['usage'] ?? []),
                        implode(' ', $item['avoid'] ?? []),
                        implode(' ', $item['accessibility'] ?? []),
                    ])
                );

                return str_contains(
                    $haystack,
                    $query,
                );
            })
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        return $this->all()
            ->firstWhere('id', $id);
    }

    /**
     * @return array<string, string>
     */
    public function categories(): array
    {
        return config(
            'web_content_library.categories',
            [],
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function statuses(): array
    {
        return config(
            'web_content_library.statuses',
            [],
        );
    }

    /**
     * @return array<string, int>
     */
    public function categoryCounts(): array
    {
        $counts = $this->all()
            ->countBy(
                fn (array $item): string => (string) ($item['category'] ?? 'unknown')
            );

        return collect($this->categories())
            ->mapWithKeys(
                fn (string $label, string $key): array => [
                    $key => (int) $counts->get($key, 0),
                ]
            )
            ->all();
    }

    /**
     * @return Collection<int, string>
     */
    public function registeredFiles(): Collection
    {
        return $this->all()
            ->flatMap(function (array $item): array {
                $files = $item['files'] ?? [];

                if (($item['source'] ?? null) !== null) {
                    $files[] = $item['source'];
                }

                return $files;
            })
            ->filter()
            ->map(
                fn (string $path): string => $this->normalizePath($path)
            )
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Files that are expected to be represented by the library registry.
     *
     * @return Collection<int, string>
     */
    public function discoveredCoreFiles(): Collection
    {
        $patterns = [
            resource_path(
                'views/components/vdbs/*.blade.php',
            ),
            resource_path(
                'css/vdbs/components/*.css',
            ),
        ];

        return collect($patterns)
            ->flatMap(
                fn (string $pattern): array => File::glob($pattern) ?: []
            )
            ->map(function (string $absolutePath): string {
                $relative = str_replace(
                    base_path().DIRECTORY_SEPARATOR,
                    '',
                    $absolutePath,
                );

                return $this->normalizePath(
                    $relative,
                );
            })
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @return array{
     *     discovered: int,
     *     registered: int,
     *     missing: list<string>
     * }
     */
    public function coverage(): array
    {
        $discovered = $this->discoveredCoreFiles();
        $registered = $this->registeredFiles();

        return [
            'discovered' => $discovered->count(),
            'registered' => $registered
                ->intersect($discovered)
                ->count(),
            'missing' => $discovered
                ->diff($registered)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function related(
        array $item,
        int $limit = 4,
    ): Collection {
        $tags = collect(
            $item['tags'] ?? []
        );

        if ($tags->isEmpty()) {
            return collect();
        }

        return $this->all()
            ->reject(
                fn (array $candidate): bool => ($candidate['id'] ?? null)
                    === ($item['id'] ?? null)
            )
            ->map(function (array $candidate) use ($tags): array {
                $score = $tags
                    ->intersect(
                        $candidate['tags'] ?? []
                    )
                    ->count();

                return [
                    'item' => $candidate,
                    'score' => $score,
                ];
            })
            ->filter(
                fn (array $entry): bool => $entry['score'] > 0
            )
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('item')
            ->values();
    }

    private function normalizePath(
        string $path,
    ): string {
        return str_replace(
            '\\',
            '/',
            $path,
        );
    }
}
