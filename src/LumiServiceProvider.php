<?php

declare(strict_types=1);

namespace Lumi\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Mail\MailManager;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Lumi\Laravel\Client\LumiClient;
use Lumi\Laravel\Http\Controllers\LumiWebhookController;
use Lumi\Laravel\Notifications\LumiChannel;
use Lumi\Laravel\Transport\LumiTransport;

class LumiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/lumi.php', 'lumi');

        $this->app->singleton(LumiClient::class, function (Application $app) {
            $config = $app['config']->get('lumi', []);
            $apiKey = $config['api_key'] ?? (string) env('LUMI_API_KEY', '');
            $baseUrl = $config['base_url'] ?? (string) env('LUMI_BASE_URL', 'https://api.lumi.email/v1');
            $timeout = (float) ($config['timeout'] ?? 30.0);

            return new LumiClient($apiKey, $baseUrl, null, $timeout);
        });

        $this->app->alias(LumiClient::class, 'lumi');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/lumi.php' => $this->app->configPath('lumi.php'),
            ], 'lumi-config');
        }

        // Register custom mail transport driver
        $this->registerMailTransport();

        // Register custom notification channel
        $this->registerNotificationChannel();

        // Register webhook routes if path is defined
        $this->registerWebhookRoute();
    }

    /**
     * Register the 'lumi' transport driver in Laravel MailManager.
     */
    protected function registerMailTransport(): void
    {
        $this->app->resolving('mail.manager', function (MailManager $mailManager) {
            $mailManager->extend('lumi', function (array $config = []) {
                $apiKey = $config['key'] ?? config('lumi.api_key') ?? (string) env('LUMI_API_KEY', '');
                $baseUrl = $config['base_url'] ?? config('lumi.base_url') ?? (string) env('LUMI_BASE_URL', 'https://api.lumi.email/v1');
                $timeout = (float) ($config['timeout'] ?? config('lumi.timeout') ?? 30.0);

                $client = new LumiClient($apiKey, $baseUrl, null, $timeout);

                return new LumiTransport($client);
            });
        });
    }

    /**
     * Register the 'lumi' notification channel.
     */
    protected function registerNotificationChannel(): void
    {
        $this->app->resolving(ChannelManager::class, function (ChannelManager $channels) {
            $channels->extend('lumi', function (Application $app) {
                return new LumiChannel($app->make(LumiClient::class));
            });
        });
    }

    /**
     * Register webhook routes.
     */
    protected function registerWebhookRoute(): void
    {
        $path = config('lumi.webhook.path');
        if (!empty($path)) {
            Route::post($path, LumiWebhookController::class)
                ->name('lumi.webhook')
                ->middleware('api');
        }
    }
}
