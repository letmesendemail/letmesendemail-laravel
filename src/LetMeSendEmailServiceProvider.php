<?php

declare(strict_types=1);

namespace LetMeSendEmail\Laravel;

use Illuminate\Support\ServiceProvider;

final class LetMeSendEmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/letmesendemail.php',
            'letmesendemail',
        );

        $this->app->singleton(LetMeSendEmail::class, function () {
            return new LetMeSendEmail(
                apiKey: (string) config('letmesendemail.api_key'),
                baseUrl: (string) config('letmesendemail.base_url'),
                timeout: (int) config('letmesendemail.timeout'),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/letmesendemail.php' => config_path('letmesendemail.php'),
            ], 'letmesendemail-config');
        }
    }
}
