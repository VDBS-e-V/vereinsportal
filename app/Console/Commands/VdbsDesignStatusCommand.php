<?php

namespace App\Console\Commands;

use App\Support\WebContentLibrary;
use Illuminate\Console\Command;

final class VdbsDesignStatusCommand extends Command
{
    protected $signature = 'vdbs:design-status';

    protected $description =
        'Zeigt Version, Freeze-Status und Bibliotheksabdeckung des VDBS-Designsystems.';

    public function handle(
        WebContentLibrary $library,
    ): int {
        $coverage = $library->coverage();
        $counts = $library->all()
            ->countBy(
                fn (array $item): string => (string) ($item['status'] ?? 'unknown')
            );

        $this->components->info(
            'VDBS Designsystem v'.
            config(
                'web_content_library.version',
                'unversioniert',
            )
        );

        $this->line(
            'Stabilitätsmodus: '.
            (
                config(
                    'web_content_library.frozen',
                    false,
                )
                    ? 'aktiv'
                    : 'inaktiv'
            )
        );

        $this->line(
            sprintf(
                'Inventar: %d/%d Kern-Dateien',
                $coverage['registered'],
                $coverage['discovered'],
            )
        );

        foreach ([
            'stable' => 'Stabil',
            'beta' => 'Beta',
            'experimental' => 'Experimentell',
            'deprecated' => 'Veraltet',
        ] as $key => $label) {
            $this->line(
                sprintf(
                    '%s: %d',
                    $label,
                    (int) $counts->get($key, 0),
                )
            );
        }

        return $coverage['missing'] === []
            ? self::SUCCESS
            : self::FAILURE;
    }
}
