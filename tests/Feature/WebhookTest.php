<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use LetMeSendEmail\Exceptions\WebhookVerificationException;
use LetMeSendEmail\Laravel\Events\WebhookReceived;
use LetMeSendEmail\Laravel\Http\Controllers\WebhookController;
use LetMeSendEmail\Laravel\Middleware\VerifyWebhookSignature;

function generateSignature(string $payload, string $secret): string
{
    $rawSecret = str_starts_with($secret, 'whsec_')
        ? substr($secret, 6)
        : $secret;

    $decodedSecret = base64_decode($rawSecret);
    $timestamp = (string) time();
    $signedPayload = "wh_id.wh_log.{$timestamp}.{$payload}";
    $hexHash = hash_hmac('sha256', $signedPayload, $decodedSecret);
    $signature = base64_encode(pack('H*', $hexHash));

    return "v1,{$signature}";
}

function buildValidHeaders(string $payload, string $secret): array
{
    $rawSecret = str_starts_with($secret, 'whsec_')
        ? substr($secret, 6)
        : $secret;

    $decodedSecret = base64_decode($rawSecret);
    $timestamp = (string) time();
    $signedPayload = "wh_id.wh_log.{$timestamp}.{$payload}";
    $hexHash = hash_hmac('sha256', $signedPayload, $decodedSecret);
    $signature = base64_encode(pack('H*', $hexHash));

    return [
        'webhook-id' => 'wh_id',
        'webhook-log-id' => 'wh_log',
        'webhook-timestamp' => $timestamp,
        'webhook-signature' => "v1,{$signature}",
    ];
}

test('webhook middleware passes with valid signature', function () {
    config()->set('letmesendemail.webhooks.signing_secret', base64_encode('test_secret'));

    $payload = json_encode(['event' => 'email.sent']);
    $headers = buildValidHeaders($payload, config('letmesendemail.webhooks.signing_secret'));

    $request = Request::create('/webhooks/letmesendemail', 'POST', [], [], [], [], $payload);
    foreach ($headers as $key => $value) {
        $request->headers->set($key, $value);
    }

    $middleware = new VerifyWebhookSignature();
    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('OK');
});

test('webhook middleware stores verified payload on request', function () {
    config()->set('letmesendemail.webhooks.signing_secret', base64_encode('test_secret'));

    $payload = json_encode(['event' => 'email.delivered', 'data' => ['id' => 'msg_1']]);
    $headers = buildValidHeaders($payload, config('letmesendemail.webhooks.signing_secret'));

    $request = Request::create('/webhooks/letmesendemail', 'POST', [], [], [], [], $payload);
    foreach ($headers as $key => $value) {
        $request->headers->set($key, $value);
    }

    $middleware = new VerifyWebhookSignature();
    $middleware->handle($request, function ($req) {
        $stored = $req->attributes->get('letmesendemail_webhook_payload');
        expect($stored)->toBe(['event' => 'email.delivered', 'data' => ['id' => 'msg_1']]);
        return response('OK');
    });
});

test('webhook middleware fails with invalid signature', function () {
    config()->set('letmesendemail.webhooks.signing_secret', base64_encode('test_secret'));

    $payload = json_encode(['event' => 'email.sent']);
    $request = Request::create('/webhooks/letmesendemail', 'POST', [], [], [], [], $payload);
    $request->headers->set('webhook-id', 'wh_id');
    $request->headers->set('webhook-log-id', 'wh_log');
    $request->headers->set('webhook-timestamp', (string) time());
    $request->headers->set('webhook-signature', 'v1,badsig');

    $middleware = new VerifyWebhookSignature();

    expect(fn () => $middleware->handle($request, fn ($req) => response('OK')))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('webhook middleware fails without configured secret', function () {
    config()->set('letmesendemail.webhooks.signing_secret', '');

    $request = Request::create('/webhooks/letmesendemail', 'POST', [], [], [], [], '{}');
    $middleware = new VerifyWebhookSignature();

    expect(fn () => $middleware->handle($request, fn ($req) => response('OK')))
        ->toThrow(WebhookVerificationException::class);
});

test('webhook controller dispatches event after middleware', function () {
    Event::fake();

    config()->set('letmesendemail.webhooks.signing_secret', base64_encode('test_secret'));

    $payload = json_encode(['event' => 'email.delivered']);
    $headers = buildValidHeaders($payload, config('letmesendemail.webhooks.signing_secret'));

    $request = Request::create('/webhooks/letmesendemail', 'POST', [], [], [], [], $payload);
    foreach ($headers as $key => $value) {
        $request->headers->set($key, $value);
    }

    $middleware = new VerifyWebhookSignature();
    $middleware->handle($request, function ($req) {
        $controller = new WebhookController();
        return $controller->__invoke($req);
    });

    Event::assertDispatched(WebhookReceived::class, fn ($event) => $event->payload['event'] === 'email.delivered');
});
