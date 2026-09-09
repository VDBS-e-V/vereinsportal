<?php

namespace App\Support;

final class SemanticExampleBuilder
{
    private const KINDS = [
        'notice' => 'Hinweis',
        'status' => 'Status',
        'badge' => 'Badge',
    ];

    private const TYPES = [
        'notice' => [
            'info' => 'Info',
            'success' => 'Erfolg',
            'warning' => 'Warnung',
            'danger' => 'Gefahr',
        ],
        'status' => [
            'neutral' => 'Neutral',
            'info' => 'Info',
            'success' => 'Erfolg',
            'warning' => 'Warnung',
            'danger' => 'Gefahr',
        ],
        'badge' => [
            'neutral' => 'Neutral',
            'accent' => 'Akzent',
        ],
    ];

    public function kinds(): array
    {
        return self::KINDS;
    }

    public function types(
        string $kind,
    ): array {
        return self::TYPES[$kind]
            ?? self::TYPES['notice'];
    }

    public function build(
        ?string $kind,
        ?string $type,
        ?string $label,
    ): array {
        $kind = array_key_exists(
            (string) $kind,
            self::KINDS,
        )
            ? (string) $kind
            : 'notice';

        $types = $this->types($kind);

        $type = array_key_exists(
            (string) $type,
            $types,
        )
            ? (string) $type
            : array_key_first($types);

        $label = trim((string) $label);

        if ($label === '') {
            $label = match ($kind) {
                'status' => 'Aktiv',
                'badge' => 'Neu',
                default => 'Bitte beachten Sie diesen Hinweis.',
            };
        }

        $label = mb_substr(
            $label,
            0,
            120,
        );

        $escaped = e($label);

        $code = match ($kind) {
            'status' => sprintf(
                '<x-vdbs.status type="%s">%s</x-vdbs.status>',
                $type,
                $escaped,
            ),
            'badge' => sprintf(
                '<x-vdbs.badge tone="%s">%s</x-vdbs.badge>',
                $type,
                $escaped,
            ),
            default => sprintf(
                '<x-vdbs.notice type="%s" role="%s">%s</x-vdbs.notice>',
                $type,
                in_array(
                    $type,
                    ['warning', 'danger'],
                    true,
                )
                    ? 'alert'
                    : 'status',
                $escaped,
            ),
        };

        return [
            'kind' => $kind,
            'type' => $type,
            'label' => $label,
            'code' => $code,
        ];
    }
}
