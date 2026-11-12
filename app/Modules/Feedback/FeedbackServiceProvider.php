<?php

namespace App\Modules\Feedback;

use App\Modules\Feedback\Console\PruneFeedback;
use App\Modules\Feedback\Console\RemindFeedback;
use Illuminate\Support\ServiceProvider;

class FeedbackServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        // Auch bei Aufrufen über den Browser (Systemsteuerung, Einrichtung) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([PruneFeedback::class, RemindFeedback::class]);
    }
}
