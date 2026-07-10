<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LetMeSendEmail\Laravel\LetMeSendEmailServiceProvider;

beforeEach(function () {
    config()->set('letmesendemail.api_key', 'lms_test_key');
});

test('webhook route is registered when enabled', function () {
    config()->set('letmesendemail.webhooks.enabled', true);
    config()->set('letmesendemail.webhooks.signing_secret', base64_encode('test_secret'));

    $provider = $this->app->getProvider(LetMeSendEmailServiceProvider::class);
    $ref = new ReflectionMethod($provider, 'registerWebhookRoutes');
    $ref->setAccessible(true);
    $ref->invoke($provider);

    $routes = Route::getRoutes();
    $matched = false;
    foreach ($routes as $route) {
        if ($route->uri() === 'webhooks/letmesendemail') {
            $matched = true;
            expect($route->methods())->toContain('POST');
            break;
        }
    }

    expect($matched)->toBeTrue();
});

test('webhook route uses verification middleware', function () {
    config()->set('letmesendemail.webhooks.enabled', true);
    config()->set('letmesendemail.webhooks.signing_secret', base64_encode('test_secret'));

    $provider = $this->app->getProvider(LetMeSendEmailServiceProvider::class);
    $ref = new ReflectionMethod($provider, 'registerWebhookRoutes');
    $ref->setAccessible(true);
    $ref->invoke($provider);

    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'webhooks/letmesendemail') {
            expect($route->gatherMiddleware())->toContain('letmesendemail.webhook');
            return;
        }
    }

    $this->fail('Webhook route not found.');
});

test('webhook route is not registered when disabled', function () {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'webhooks/letmesendemail') {
            $this->fail('Webhook route should not be registered when disabled.');
        }
    }

    expect(true)->toBeTrue();
});

test('webhook raw payload verification through registered route', function () {
    config()->set('letmesendemail.webhooks.enabled', true);
    config()->set('letmesendemail.webhooks.signing_secret', base64_encode('test_secret'));

    $provider = $this->app->getProvider(LetMeSendEmailServiceProvider::class);
    $ref = new ReflectionMethod($provider, 'registerWebhookRoutes');
    $ref->setAccessible(true);
    $ref->invoke($provider);

    $payload = json_encode(['event' => 'email.bounced']);
    $rawSecret = base64_encode('test_secret');
    $decodedSecret = base64_decode($rawSecret);
    $timestamp = (string) time();
    $signedPayload = "wh_id.wh_log.{$timestamp}.{$payload}";
    $hexHash = hash_hmac('sha256', $signedPayload, $decodedSecret);
    $signature = base64_encode(pack('H*', $hexHash));

    $headers = [
        'webhook-id' => 'wh_id',
        'webhook-log-id' => 'wh_log',
        'webhook-timestamp' => $timestamp,
        'webhook-signature' => "v1,{$signature}",
    ];

    $response = $this->postJson('/webhooks/letmesendemail', json_decode($payload, true), $headers);

    $response->assertStatus(200);
    $response->assertJson(['received' => true]);
});
