<?php

declare(strict_types=1);

test('config loads default values', function () {
    expect(config('letmesendemail.api_key'))->toBe('lms_live_test_key');
    expect(config('letmesendemail.base_url'))->toBe('https://letmesend.email/api/v1');
    expect(config('letmesendemail.timeout'))->toBe(30);
});

test('facade returns an instance', function () {
    $client = \LetMeSendEmail\Laravel\Facades\LetMeSendEmail::getFacadeRoot();

    expect($client)->toBeInstanceOf(\LetMeSendEmail\Laravel\LetMeSendEmail::class);
});

test('facade provides email resource', function () {
    expect(\LetMeSendEmail\Laravel\Facades\LetMeSendEmail::emails())
        ->toBeInstanceOf(\LetMeSendEmail\Resources\EmailsResource::class);
});

test('facade provides domain resource', function () {
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
