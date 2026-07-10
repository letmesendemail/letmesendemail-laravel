<?php

declare(strict_types=1);

use LetMeSendEmail\Laravel\LetMeSendEmail;

beforeEach(function () {
    config()->set('letmesendemail.api_key', 'lms_live_test_key');
    config()->set('letmesendemail.base_url', 'https://letmesend.email/api/v1');
    config()->set('letmesendemail.timeout', 60);
    config()->set('letmesendemail.retries', 3);
});

test('service provider registers singleton', function () {
    $instance = app(LetMeSendEmail::class);
    $same = app(LetMeSendEmail::class);

    expect($instance)->toBeInstanceOf(LetMeSendEmail::class);
    expect($same)->toBe($instance);
});

test('facade binding via alias', function () {
    $aliased = app('letmesendemail');
    expect($aliased)->toBeInstanceOf(LetMeSendEmail::class);
});

test('config is merged', function () {
    expect(config('letmesendemail.api_key'))->toBe('lms_live_test_key');
    expect(config('letmesendemail.base_url'))->toBe('https://letmesend.email/api/v1');
    expect(config('letmesendemail.timeout'))->toBe(60);
    expect(config('letmesendemail.retries'))->toBe(3);
});

test('services config fallback', function () {
    config()->set('letmesendemail.api_key', '');
    config()->set('services.letmesendemail.key', 'fallback_key');
    app()->forgetInstance(LetMeSendEmail::class);

    $client = app(LetMeSendEmail::class);
    $ref = new ReflectionClass($client);
    $clientProp = $ref->getProperty('client');
    $clientProp->setAccessible(true);
    $inner = $clientProp->getValue($client);

    $configGetter = new ReflectionMethod($inner, 'getConfiguration');
    $configGetter->setAccessible(true);
    $cfg = $configGetter->invoke($inner);

    expect($cfg->getApiKey())->toBe('fallback_key');
});

test('facade provides emails resource', function () {
    expect(\LetMeSendEmail\Laravel\Facades\LetMeSendEmail::emails())
        ->toBeInstanceOf(\LetMeSendEmail\Resources\EmailsResource::class);
});

test('facade provides domains resource', function () {
    expect(\LetMeSendEmail\Laravel\Facades\LetMeSendEmail::domains())
        ->toBeInstanceOf(\LetMeSendEmail\Resources\DomainsResource::class);
});

test('facade provides contacts resource', function () {
    expect(\LetMeSendEmail\Laravel\Facades\LetMeSendEmail::contacts())
        ->toBeInstanceOf(\LetMeSendEmail\Resources\ContactsResource::class);
});

test('facade provides contact categories resource', function () {
    expect(\LetMeSendEmail\Laravel\Facades\LetMeSendEmail::contactCategories())
        ->toBeInstanceOf(\LetMeSendEmail\Resources\ContactCategoriesResource::class);
});

test('facade provides email topics resource', function () {
    expect(\LetMeSendEmail\Laravel\Facades\LetMeSendEmail::emailTopics())
        ->toBeInstanceOf(\LetMeSendEmail\Resources\EmailTopicsResource::class);
});

test('retry count reaches core Configuration', function () {
    config()->set('letmesendemail.retries', 5);
    app()->forgetInstance(LetMeSendEmail::class);

    $client = app(LetMeSendEmail::class);
    $ref = new ReflectionClass($client);
    $clientProp = $ref->getProperty('client');
    $clientProp->setAccessible(true);
    $inner = $clientProp->getValue($client);

    $configGetter = new ReflectionMethod($inner, 'getConfiguration');
    $configGetter->setAccessible(true);
    $cfg = $configGetter->invoke($inner);

    expect($cfg->getRetries())->toBe(5);
});
