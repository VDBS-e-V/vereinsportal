<?php

namespace App\Providers;

use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as IlluminateView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(
            'components.vdbs.portal-header',
            static function (IlluminateView $view): void {
                $account = $view->getData()['account'] ?? null;

                if (! is_array($account) || ($account['avatar_url'] ?? null) !== null) {
                    return;
                }

                $user = Auth::user();

                if (! $user instanceof User) {
                    return;
                }

                $account['avatar_url'] = $user->avatarUrl();
                $view->with('account', $account);
            },
        );
    }
}
