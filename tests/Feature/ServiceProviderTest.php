<?php

declare(strict_types=1);

use LetMeSendEmail\Laravel\LetMeSendEmail;

test('service provider registers singleton', function () {
    $instance = app(LetMeSendEmail::class);
    $same = app(LetMeSendEmail::class);

    expect($instance)->toBeInstanceOf(LetMeSendEmail::class);
    expect($same)->toBe($instance);
});

test('service provider merges config', function () {
    expect(config()->has('letmesendemail'))->toBeTrue();
    expect(config('letmesendemail.api_key'))->toBe('lms_live_test_key');
});

test('config can be overridden', function () {
    config()->set('letmesendemail.base_url', 'https://custom.example.com/api');

    $client = app(LetMeSendEmail::class);
    $reflection = new ReflectionClass($client);
    $clientProp = $reflection->getProperty('client');
    $clientProp->setAccessible(true);
    $inner = $clientProp->getValue($client);

    $innerReflection = new ReflectionClass($inner);
    $clientField = $innerReflection->getProperty('client');
    $clientField->setAccessible(true);
    $httpClient = $clientField->getValue($inner);

    $configGetter = new ReflectionMethod($httpClient, 'getConfiguration');
    $configGetter->setAccessible(true);
    $cfg = $configGetter->invoke($httpClient);

    expect($cfg->getBaseUrl())->toBe('https://custom.example.com/api');
});
