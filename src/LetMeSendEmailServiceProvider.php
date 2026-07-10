<?php

declare(strict_types=1);

namespace LetMeSendEmail\Laravel;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LetMeSendEmail\Laravel\Http\Controllers\WebhookController;
use LetMeSendEmail\Laravel\Middleware\VerifyWebhookSignature;
use LetMeSendEmail\Laravel\Transport\LetMeSendEmailTransport;

final class LetMeSendEmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/letmesendemail.php',
            'letmesendemail',
        );

        $this->app->singleton(LetMeSendEmail::class, function () {
            $apiKey = (string) config('letmesendemail.api_key');
            if ($apiKey === '') {
                $apiKey = (string) config('services.letmesendemail.key', '');
            }
            return new LetMeSendEmail(
                apiKey: $apiKey,
                baseUrl: (string) config('letmesendemail.base_url'),
                timeout: (int) config('letmesendemail.timeout'),
                retries: (int) config('letmesendemail.retries'),
            );
        });

        $this->app->alias(LetMeSendEmail::class, 'letmesendemail');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/letmesendemail.php' => config_path('letmesendemail.php'),
            ], 'letmesendemail-config');
        }

        $this->app->make('router')->aliasMiddleware('letmesendemail.webhook', VerifyWebhookSignature::class);

        $this->registerWebhookRoutes();
        $this->registerMailTransport();
    }

    private function registerMailTransport(): void
    {
        Mail::extend('letmesendemail', function (array $config = []) {
            return new LetMeSendEmailTransport(
                client: $this->app->make(LetMeSendEmail::class),
            );
        });
    }

    private function registerWebhookRoutes(): void
    {
        if (!(bool) config('letmesendemail.webhooks.enabled', false)) {
            return;
        }

        Route::post(
            (string) config('letmesendemail.webhooks.path', '/webhooks/letmesendemail'),
            WebhookController::class,
        )->middleware((array) config('letmesendemail.webhooks.middleware', ['letmesendemail.webhook']));
    }
}
