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
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Extend Socialite for Discord
        Event::listen(SocialiteWasCalled::class, [Provider::class, 'handle']);

        // Set default URL options for HTTPS if needed
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Ensure that the default timezone is set correctly
        date_default_timezone_set(config('app.timezone', 'UTC'));

        // Bootstrap any package services
        $this->bootSocialite();

        // Boot your custom view composers or other bindings
        View::composer('*', function ($view) {
            // Example: Share data with all views
            $view->with('site_name', config('app.name'));
        });
    }

    protected function bootSocialite()
    {
        Event::listen(SocialiteWasCalled::class, [Provider::class, 'handle']);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register your service providers here
    }
}

