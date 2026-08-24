<?php

namespace SameOldNick\Ntfy;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use SameOldNick\Ntfy\Channels\NtfyChannel;
use SameOldNick\Ntfy\Services\Ntfy;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Ntfy::class, function ($app) {
            return new Ntfy;
        });

        $this->app->alias(Ntfy::class, 'ntfy');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Notification::resolved(function (ChannelManager $manager) {
            $manager->extend('ntfy', function ($app) {
                return new NtfyChannel($app->make(Ntfy::class));
            });
        });

        $this->publishes([
            __DIR__.'/../config/ntfy.php' => config_path('ntfy.php'),
        ], 'ntfy-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'ntfy-migrations');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'ntfy');
        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/ntfy'),
        ], 'ntfy-translations');
    }
}
