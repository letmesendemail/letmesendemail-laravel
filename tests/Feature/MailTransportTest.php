<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use LetMeSendEmail\Laravel\LetMeSendEmail;
use LetMeSendEmail\Laravel\Tests\Fakes\RecordingTransport;
use LetMeSendEmail\Laravel\Transport\LetMeSendEmailTransport;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;

beforeEach(function () {
    config()->set('mail.default', 'letmesendemail');
    config()->set('mail.mailers.letmesendemail', [
        'transport' => 'letmesendemail',
    ]);
});

test('transport is registered through Laravel Mail', function () {
    $transport = app('mailer')->getSymfonyTransport(Mail::mailer());
    expect($transport)->toBeInstanceOf(LetMeSendEmailTransport::class);
});

test('successful send with complete payload', function () {
    $recording = new RecordingTransport();
    $recording->responses = [
        ['status' => 200, 'headers' => [], 'body' => ['id' => 'email_abc123', 'status' => 'accepted', 'emails' => ['recipient@example.com'], 'restricted_emails' => []]],
    ];

    $client = new LetMeSendEmail(apiKey: 'lms_test_key', transport: $recording);
    $transport = new LetMeSendEmailTransport($client);

    $email = (new Email())
        ->from('Sender <sender@example.com>')
        ->to('recipient@example.com')
        ->cc('cc@example.com')
        ->bcc('bcc@example.com')
        ->replyTo('reply@example.com')
        ->subject('Test Subject')
        ->html('<p>HTML body</p>')
        ->text('Text body')
        ->attach('file content', 'report.txt', 'text/plain');

    $transport->send($email);

    expect($recording->requests)->toHaveCount(1);

    $request = $recording->requests[0];
    expect($request['method'])->toBe('POST');
    expect($request['uri'])->toEndWith('/emails');

    $requestHeaders = $request['options']['headers'];
    expect($requestHeaders['Authorization'])->toStartWith('Bearer lms_test_key');
    expect($requestHeaders['Content-Type'])->toBe('application/json');
    expect($requestHeaders['Accept'])->toBe('application/json');
    expect($requestHeaders['User-Agent'])->toStartWith('letmesendemail-php/');

    $body = $request['options']['body'];
    expect($body['from'])->toBe('Sender <sender@example.com>');
    expect($body['to'])->toBe(['recipient@example.com']);
    expect($body['subject'])->toBe('Test Subject');
    expect($body['html'])->toBe('<p>HTML body</p>');
    expect($body['text'])->toBe('Text body');
    expect($body['cc'])->toBe(['cc@example.com']);
    expect($body['bcc'])->toBe(['bcc@example.com']);
    expect($body['reply_to'])->toBe(['reply@example.com']);

    expect($body['attachments'])->toHaveCount(1);
    $att = $body['attachments'][0];
    expect($att['name'])->toBe('report.txt');
    expect($att['content'])->toBe(base64_encode('file content'));
    expect($att['content_disposition'])->toBe('attachment');
});

test('structural MIME headers are absent from custom headers', function () {
    $recording = new RecordingTransport();
    $client = new LetMeSendEmail(apiKey: 'lms_test_key', transport: $recording);
    $transport = new LetMeSendEmailTransport($client);

    $email = (new Email())
        ->from('from@example.com')
        ->to('to@example.com')
        ->subject('Test')
        ->text('Body');

    $email->getHeaders()->addTextHeader('X-Custom', 'custom-value');

    $transport->send($email);

    $customHeaders = $recording->requests[0]['options']['body']['headers'] ?? [];
    expect($customHeaders)->toHaveKey('X-Custom');

    $forbidden = ['From', 'To', 'Cc', 'Bcc', 'Reply-To', 'Subject',
        'Content-Type', 'MIME-Version', 'Date', 'Message-ID',
        'Sender', 'Return-Path'];

    foreach ($forbidden as $name) {
        expect($customHeaders)->not->toHaveKey($name);
    }
});

test('structural header filtering is case-insensitive', function () {
    $recording = new RecordingTransport();
    $client = new LetMeSendEmail(apiKey: 'lms_test_key', transport: $recording);
    $transport = new LetMeSendEmailTransport($client);

    $email = (new Email())
        ->from('from@example.com')
        ->to('to@example.com')
        ->subject('Test')
        ->text('Body');

    $email->getHeaders()->addTextHeader('X-Custom', 'present');
    $email->getHeaders()->addTextHeader('X-DATE', 'should-pass');

    $transport->send($email);

    $customHeaders = $recording->requests[0]['options']['body']['headers'] ?? [];
    expect($customHeaders)->toHaveKey('X-Custom');
    expect($customHeaders)->toHaveKey('X-DATE');
    expect($customHeaders)->not->toHaveKey('Date');
    expect($customHeaders)->not->toHaveKey('From');
    expect($customHeaders)->not->toHaveKey('Subject');
});

test('Idempotency-Key is extracted and sent as request header', function () {
    $recording = new RecordingTransport();
    $client = new LetMeSendEmail(apiKey: 'lms_test_key', transport: $recording);
    $transport = new LetMeSendEmailTransport($client);

    $email = (new Email())
        ->from('from@example.com')
        ->to('to@example.com')
        ->subject('Test')
        ->text('Body');

    $email->getHeaders()->addTextHeader('Idempotency-Key', 'my-idem-key');

    $transport->send($email);

    $requestHeaders = $recording->requests[0]['options']['headers'];
    expect($requestHeaders['Idempotency-Key'])->toBe('my-idem-key');
    expect($recording->requests[0]['options']['body']['headers'] ?? [])->not->toHaveKey('Idempotency-Key');
});

test('Idempotency-Key is detected case-insensitively', function () {
    $recording = new RecordingTransport();
    $client = new LetMeSendEmail(apiKey: 'lms_test_key', transport: $recording);
    $transport = new LetMeSendEmailTransport($client);

    $email = (new Email())
        ->from('from@example.com')
        ->to('to@example.com')
        ->subject('Test')
        ->text('Body');

    $email->getHeaders()->addTextHeader('idempotency-key', 'lowercase-key');

    $transport->send($email);

    $requestHeaders = $recording->requests[0]['options']['headers'];
    expect($requestHeaders['Idempotency-Key'])->toBe('lowercase-key');
});

test('response message ID equals letmesend.email ID', function () {
    $recording = new RecordingTransport();
    $recording->responses = [
        ['status' => 200, 'headers' => [], 'body' => ['id' => 'email_xyz789', 'status' => 'accepted', 'emails' => ['to@example.com'], 'restricted_emails' => []]],
    ];

    $client = new LetMeSendEmail(apiKey: 'lms_test_key', transport: $recording);
    $transport = new LetMeSendEmailTransport($client);

    $email = (new Email())
        ->from('from@example.com')
        ->to('to@example.com')
        ->subject('Test')
        ->text('Body');

    $sentMessage = $transport->send($email);

    expect($sentMessage->getMessageId())->toBe('email_xyz789');
});

test('API exception converts to TransportException', function () {
    $recording = new RecordingTransport();
    $recording->responses = [
        ['status' => 422, 'headers' => [], 'body' => ['name' => 'validation_error', 'message' => 'Invalid data', 'errors' => []]],
    ];

    $client = new LetMeSendEmail(apiKey: 'lms_test_key', transport: $recording);
    $transport = new LetMeSendEmailTransport($client);

    $email = (new Email())
        ->from('from@example.com')
        ->to('to@example.com')
        ->subject('Test')
        ->text('Body');

    expect(fn () => $transport->send($email))
        ->toThrow(TransportException::class);
});

test('convertToPayload rejects missing sender', function () {
    $recording = new RecordingTransport();
    $client = new LetMeSendEmail(apiKey: 'lms_test_key', transport: $recording);
    $transport = new LetMeSendEmailTransport($client);

    $ref = new ReflectionMethod($transport, 'convertToPayload');
    $ref->setAccessible(true);

    $email = (new Email())
        ->to('to@example.com')
        ->subject('Test')
        ->text('Body');

    $payload = $ref->invoke($transport, $email);
    expect($payload['from'])->toBe('');
});

test('convertToPayload rejects missing recipients', function () {
    $recording = new RecordingTransport();
    $client = new LetMeSendEmail(apiKey: 'lms_test_key', transport: $recording);
    $transport = new LetMeSendEmailTransport($client);

    $ref = new ReflectionMethod($transport, 'convertToPayload');
    $ref->setAccessible(true);

    $email = (new Email())
        ->from('from@example.com')
        ->subject('Test')
        ->text('Body');

    $payload = $ref->invoke($transport, $email);
    expect($payload['to'])->toBe([]);
});

test('attachment serialization uses core Attachment API', function () {
    $recording = new RecordingTransport();
    $client = new LetMeSendEmail(apiKey: 'lms_test_key', transport: $recording);
    $transport = new LetMeSendEmailTransport($client);

    $email = (new Email())
        ->from('from@example.com')
        ->to('to@example.com')
        ->subject('Test')
        ->text('Body')
        ->attach('binary data', 'file.bin', 'application/octet-stream');

    $transport->send($email);

    $body = $recording->requests[0]['options']['body'];
    expect($body['attachments'])->toHaveCount(1);
    $att = $body['attachments'][0];
    expect($att['name'])->toBe('file.bin');
    expect($att['content'])->toBe(base64_encode('binary data'));
    expect($att['content_disposition'])->toBe('attachment');
});
