<?php

declare(strict_types=1);

namespace Mailifica\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Mail\MailManager;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mailifica\Laravel\Client\MailificaClient;
use Mailifica\Laravel\Http\Controllers\MailificaWebhookController;
use Mailifica\Laravel\Notifications\MailificaChannel;
use Mailifica\Laravel\Transport\MailificaTransport;

class MailificaServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mailifica.php', 'mailifica');

        $this->app->singleton(MailificaClient::class, function (Application $app) {
            $config = $app['config']->get('mailifica', []);
            $apiKey = $config['api_key'] ?? (string) env('MAILIFICA_API_KEY', '');
            $baseUrl = $config['base_url'] ?? (string) env('MAILIFICA_BASE_URL', 'https://api.mailifica.com/v1');
            $timeout = (float) ($config['timeout'] ?? 30.0);

            return new MailificaClient($apiKey, $baseUrl, null, $timeout);
        });

        $this->app->alias(MailificaClient::class, 'mailifica');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/mailifica.php' => $this->app->configPath('mailifica.php'),
            ], 'mailifica-config');
        }

        // Register custom mail transport driver
        $this->registerMailTransport();

        // Register custom notification channel
        $this->registerNotificationChannel();

        // Register webhook routes if path is defined
        $this->registerWebhookRoute();
    }

    /**
     * Register the 'mailifica' transport driver in Laravel MailManager.
     */
    protected function registerMailTransport(): void
    {
        $this->app->resolving('mail.manager', function (MailManager $mailManager) {
            $mailManager->extend('mailifica', function (array $config = []) {
                $apiKey = $config['key'] ?? config('mailifica.api_key') ?? (string) env('MAILIFICA_API_KEY', '');
                $baseUrl = $config['base_url'] ?? config('mailifica.base_url') ?? (string) env('MAILIFICA_BASE_URL', 'https://api.mailifica.com/v1');
                $timeout = (float) ($config['timeout'] ?? config('mailifica.timeout') ?? 30.0);

                $client = new MailificaClient($apiKey, $baseUrl, null, $timeout);

                return new MailificaTransport($client);
            });
        });
    }

    /**
     * Register the 'mailifica' notification channel.
     */
    protected function registerNotificationChannel(): void
    {
        $this->app->resolving(ChannelManager::class, function (ChannelManager $channels) {
            $channels->extend('mailifica', function (Application $app) {
                return new MailificaChannel($app->make(MailificaClient::class));
            });
        });
    }

    /**
     * Register webhook routes.
     */
    protected function registerWebhookRoute(): void
    {
        $path = config('mailifica.webhook.path');
        if (!empty($path)) {
            Route::post($path, MailificaWebhookController::class)
                ->name('mailifica.webhook')
                ->middleware('api');
        }
    }
}
