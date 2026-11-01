<?php

namespace App\Modules\DesignLibrary\Console;

use App\Modules\DesignLibrary\Support\DesignScaffolder;
use App\Modules\DesignLibrary\Support\Registry;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

class DesignStatusCommand extends Command
{
    protected $signature = 'design:status {package? : Nur dieses Paket} {--set= : Stand einer Komponente setzen, z. B. button:in-progress (open, in-progress, done)}';

    protected $description = 'Zeigt den Stand der Umgestaltung der Design-Pakete und setzt ihn je Komponente';

    public function handle(): int
    {
        if (filled($this->option('set'))) {
            return $this->set();
        }

        $rows = [];
        $total = 0;
        $done = 0;

        foreach (Registry::packages() as $key => $package) {
            if ($this->argument('package') !== null && $this->argument('package') !== $key) {
                continue;
            }

            foreach ($package['components'] as $component) {
                $state = $component['redesign'] ?? 'open';
                $rows[] = [$key, $component['name'], DesignScaffolder::REDESIGN_STATES[$state] ?? $state];
                $total++;
                $done += $state === 'done' ? 1 : 0;
            }
        }

        $this->table(['Paket', 'Komponente', 'Stand'], $rows);
        $this->info("{$done} von {$total} Komponenten fertig.");

        return self::SUCCESS;
    }

    private function set(): int
    {
        $package = (string) $this->argument('package');
        [$name, $state] = array_pad(explode(':', (string) $this->option('set'), 2), 2, '');

        if ($package === '' || $name === '' || $state === '') {
            $this->error('Beispiel: php artisan design:status actions --set=button:in-progress');

            return self::FAILURE;
        }

        try {
            (new DesignScaffolder(base_path(), base_path('stubs/design')))->setRedesign($package, $name, $state);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("{$package}/{$name}: {$state}");

        return self::SUCCESS;
    }
}
