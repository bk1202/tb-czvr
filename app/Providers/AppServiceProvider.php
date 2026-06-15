<?php

namespace App\Providers;

use App\Models\Settings\CoreSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Discord\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('discord', Provider::class);
        });

        if ($this->app->environment('production')) {
            if (config('app.url')) {
                URL::forceRootUrl(config('app.url'));
            }
            URL::forceScheme('https');
        }

        View::composer('*', function ($view) {
            try {
                $settings = Cache::remember('core_settings', 300, function () {
                    return CoreSettings::find(1);
                });
            } catch (\Exception $e) {
                $settings = null;
            }
            $view->with('coreSettings', $settings);
        });
    }

    public function register(): void
    {
        //
    }
}
