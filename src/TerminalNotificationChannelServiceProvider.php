<?php

namespace ChinLeung\TerminalNotificationChannel;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class TerminalNotificationChannelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('terminal-notification.php'),
            ], 'config');
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'terminal-config');

        Notification::resolved(static function (ChannelManager $service) {
            $service->extend(
                'terminal',
                static fn ($application) => new Router($application)
            );
        });
    }
}
