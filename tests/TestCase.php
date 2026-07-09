<?php

declare(strict_types=1);

namespace LetMeSendEmail\Laravel\Tests;

use LetMeSendEmail\Laravel\LetMeSendEmailServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LetMeSendEmailServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('letmesendemail.api_key', 'lms_live_test_key');
        $app['config']->set('letmesendemail.base_url', 'https://letmesend.email/api/v1');
    }
}
