# letmesend.email SDK for Laravel

The official Laravel package for the [letmesend.email](https://letmesend.email/) API.

## Overview

This package integrates the letmesend.email API into Laravel. It provides a service
provider with auto-discovery, a publishable config file, a facade for quick access,
a Laravel mail transport, webhook route and middleware, and an event dispatched on
verified webhook delivery.

All API operations use the underlying PHP SDK (`letmesendemail/letmesendemail-php`).

## Requirements

- PHP 8.1 or later
- Laravel 10, 11, 12, or 13
- `letmesendemail/letmesendemail-php` ^0.2

## Installation

```bash
composer require letmesendemail/letmesendemail-laravel
```

The service provider is registered automatically via Composer's Laravel auto-discovery.

## Configuration

### Environment Variables

Set your API key in `.env`:

```env
LETMESENDEMAIL_API_KEY=lms_live_your_api_key_here
```

Optionally configure the base URL, timeout, and retries:

```env
LETMESENDEMAIL_BASE_URL=https://letmesend.email/api/v1
LETMESENDEMAIL_TIMEOUT=30
LETMESENDEMAIL_RETRIES=3
```

Webhook settings:

```env
LETMESENDEMAIL_WEBHOOKS_ENABLED=true
LETMESENDEMAIL_WEBHOOK_SECRET=whsec_your_signing_secret
LETMESENDEMAIL_WEBHOOK_PATH=/webhooks/letmesendemail
```

### Environment Variable Reference

| Variable | Default | Description |
|----------|---------|-------------|
| `LETMESENDEMAIL_API_KEY` | — | Your letmesend.email API key |
| `LETMESENDEMAIL_BASE_URL` | `https://letmesend.email/api/v1` | API base URL |
| `LETMESENDEMAIL_TIMEOUT` | `30` | Request timeout in seconds |
| `LETMESENDEMAIL_RETRIES` | `0` | Retry attempts for transient failures |
| `LETMESENDEMAIL_WEBHOOK_SECRET` | — | Webhook signing secret |
| `LETMESENDEMAIL_WEBHOOKS_ENABLED` | `false` | Enable webhook route |
| `LETMESENDEMAIL_WEBHOOK_PATH` | `/webhooks/letmesendemail` | Webhook URI path |

### Publishing Configuration

```bash
php artisan vendor:publish --tag=letmesendemail-config
```

This publishes `config/letmesendemail.php` to your application's `config/` directory.

### Explicit Client Construction

For tests, multi-tenant applications, or custom setups:

```php
use LetMeSendEmail\Laravel\LetMeSendEmail;

$client = new LetMeSendEmail(
    apiKey: 'lms_live_...',
    baseUrl: 'https://letmesend.email/api/v1',
    timeout: 60,
    retries: 5,
);
```

You may also inject a preconfigured core client or a transport for testing:

```php
use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Http\TransportInterface;
use LetMeSendEmail\Laravel\LetMeSendEmail;

$transport = new class implements TransportInterface {
    public function request(string $method, string $uri, array $options = []): array
    {
        // return mock response
    }
};

$client = new LetMeSendEmail(
    apiKey: 'lms_live_...',
    transport: $transport,
);
```

### Service Container Registration

The package registers `LetMeSendEmail\Laravel\LetMeSendEmail` as a singleton in the
service container, aliased to `'letmesendemail'`. It reads configuration from
`config('letmesendemail.*')`.

The API key is resolved from `config('letmesendemail.api_key')`. If empty, it falls
back to `config('services.letmesendemail.key')`.

## Quick Start

```php
<?php

use LetMeSendEmail\Laravel\Facades\LetMeSendEmail;

$email = LetMeSendEmail::emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    subject: 'Welcome',
    html: '<p>Hello from letmesend.email</p>',
);

echo $email->getId();
```

## Facade

```php
use LetMeSendEmail\Laravel\Facades\LetMeSendEmail;

// Available resources
LetMeSendEmail::emails();
LetMeSendEmail::domains();
LetMeSendEmail::contacts();
LetMeSendEmail::contactCategories();
LetMeSendEmail::emailTopics();
```

## Emails

All email operations are on `LetMeSendEmail::emails()`.

### Send an Email

```php
$email = LetMeSendEmail::emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com', 'Jane <jane@example.com>'],
    subject: 'Welcome to letmesend.email',
    html: '<h1>Welcome!</h1><p>Thanks for signing up.</p>',
    text: 'Welcome! Thanks for signing up.',
    type: 'transactional',
    eventName: 'user.created',
    emailTopicId: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
    replyTo: ['support@acme.com'],
    cc: ['manager@acme.com'],
    bcc: ['archive@acme.com'],
    headers: ['X-Custom-Header' => 'value'],
);

echo $email->getId();
echo $email->getStatus();
```

### Send with a Template

```php
$email = LetMeSendEmail::emails()->sendWithTemplate(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    templateId: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
    subject: 'Your order confirmation',
    templateVariables: [
        ['key' => 'USER_NAME', 'type' => 'string', 'value' => 'John'],
    ],
);
```

### Attachments

Provide attachments as raw arrays:

```php
$email = LetMeSendEmail::emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    subject: 'With attachment',
    attachments: [
        [
            'name' => 'report.pdf',
            'path' => 'https://storage.example.com/report.pdf',
        ],
        [
            'name' => 'data.txt',
            'content' => base64_encode('file content here'),
        ],
        [
            'name' => 'logo.png',
            'content' => base64_encode(file_get_contents(resource_path('img/logo.png'))),
            'content_id' => 'logo_cid',
            'content_disposition' => 'inline',
        ],
    ],
);
```

### Idempotency

```php
$email = LetMeSendEmail::emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    subject: 'Your invoice',
    html: '<p>Invoice attached</p>',
    idempotencyKey: 'my-unique-key-abc123',
);

if ($email->isDuplicate()) {
    // This send was a duplicate — the original was not re-attempted.
}
```

### Verify an Email Address

```php
$result = LetMeSendEmail::emails()->verify('person@example.com');

echo $result->getStatus();      // "valid", "invalid", or "risky"
echo $result->getScore();       // 0-100
echo $result->hasMailbox();     // true/false
echo $result->isDisposable();   // true/false
```

### List Emails

```php
$list = LetMeSendEmail::emails()->list(perPage: 20);

foreach ($list->items() as $email) {
    $subject = $email->getSubject() ?? '(no subject)';
    echo $email->getId() . ' - ' . $subject . PHP_EOL;
}

$pagination = $list->pagination();
echo $pagination->hasMore();   // bool
echo $pagination->getTotal();  // int

// Next page (safe for empty results)
if ($pagination->hasMore() && count($list->items()) > 0) {
    $items = $list->items();
    $lastId = $items[count($items) - 1]->getId();
    $nextPage = LetMeSendEmail::emails()->list(perPage: 20, after: $lastId);
}

// Previous page (from a page other than the first)
if (count($list->items()) > 0) {
    $items = $list->items();
    $firstId = $items[0]->getId();
    $prevPage = LetMeSendEmail::emails()->list(perPage: 20, before: $firstId);
}
```

### Get a Single Email

```php
$email = LetMeSendEmail::emails()->get('01kvv5dv472evp42a60sy4p7zx');

echo $email->getStatus();
echo $email->getSubject();
echo $email->getRecipientsCount();
echo $email->getAttachmentsCount();

// Typed recipient objects
foreach ($email->getRecipients() as $recipient) {
    echo $recipient->getEmailAddress();
    echo $recipient->getStatus();
    echo $recipient->getOpenCount();
}

// Typed attachment objects
foreach ($email->getAttachments() as $attachment) {
    echo $attachment->getName();
    echo $attachment->getMime();
    echo $attachment->getSize();
    echo $attachment->getDownloadUrl();
}
```

## Domains

All domain operations are on `LetMeSendEmail::domains()`.

```php
// List
$list = LetMeSendEmail::domains()->list(perPage: 20);
foreach ($list->items() as $domain) {
    echo $domain->getDomainName() . ' (' . $domain->getStatus() . ')' . PHP_EOL;
}

// Get
$domain = LetMeSendEmail::domains()->get('domain_id');
echo $domain->getDomainName(); // "example.com"

// Verify
$result = LetMeSendEmail::domains()->verify('example.com');
echo $result->getStatus(); // "verified"
```

## Contacts

All contact operations are on `LetMeSendEmail::contacts()`.

```php
// Create
$contact = LetMeSendEmail::contacts()->create(
    email: 'john@example.com',
    firstName: 'John',
    lastName: 'Doe',
    phone: '+1234567890',
    categories: ['category_id'],
);

// List
$list = LetMeSendEmail::contacts()->list(perPage: 10);

// Get
$contact = LetMeSendEmail::contacts()->get('contact_id');

// Update
$updated = LetMeSendEmail::contacts()->update(
    'contact_id',
    firstName: 'Jane',
    syncCategories: true,
);

// Delete
$result = LetMeSendEmail::contacts()->delete('contact_id');
echo $result->getStatus(); // "success"
```

## Contact Categories

All category operations are on `LetMeSendEmail::contactCategories()`.

```php
// Create
$category = LetMeSendEmail::contactCategories()->create(name: 'Newsletter');

// List
$list = LetMeSendEmail::contactCategories()->list(perPage: 20);

// Get
$category = LetMeSendEmail::contactCategories()->get('category_id');

// Update
$category = LetMeSendEmail::contactCategories()->update(
    id: 'category_id',
    name: 'Updated Name',
    slug: 'updated-name',
);

// Delete
$result = LetMeSendEmail::contactCategories()->delete('category_id');
```

## Email Topics

All topic operations are on `LetMeSendEmail::emailTopics()`.

```php
// Create
$topic = LetMeSendEmail::emailTopics()->create(
    name: 'Product Updates',
    slug: 'product-updates',
    description: 'Emails for product updates',
    autoSubscribe: true,
    public: true,
);

// List
$list = LetMeSendEmail::emailTopics()->list(perPage: 20);

// Get
$topic = LetMeSendEmail::emailTopics()->get('topic_id');

// Update
$topic = LetMeSendEmail::emailTopics()->update(
    id: 'topic_id',
    name: 'Updated Name',
    public: true,
);

// Delete
$result = LetMeSendEmail::emailTopics()->delete('topic_id');
```

## Laravel Mail Transport

Send emails through Laravel's mail system using the `letmesendemail` transport.

### Configuration

```env
MAIL_MAILER=letmesendemail
```

Or in `config/mail.php`:

```php
'mailers' => [
    'letmesendemail' => [
        'transport' => 'letmesendemail',
    ],
],
```

### Sending a Mailable

```php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function build(): static
    {
        return $this
            ->from('noreply@acme.com')
            ->subject('Welcome!')
            ->html('<h1>Welcome</h1>');
    }
}
```

```php
Mail::to('user@example.com')->send(new WelcomeEmail());
```

### Attachments

```php
$this->attachFromStorage('/path/to/report.pdf');
$this->attachData('file content', 'report.txt', ['mime' => 'text/plain']);
```

Structural MIME headers (From, To, Cc, Bcc, Reply-To, Subject, Content-Type, MIME-Version,
Date, Message-ID, Sender, Return-Path) are automatically excluded from API custom headers.

### Idempotency

```php
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

Mail::to('user@example.com')->send(
    (new WelcomeEmail())
        ->withSymfonyMessage(function (Email $message) {
            $message->getHeaders()->addTextHeader('Idempotency-Key', 'my-unique-key');
        }),
);
```

### Queue

```php
Mail::to('user@example.com')->queue(new WelcomeEmail());
```

### Testing Mail

```php
use Illuminate\Support\Facades\Mail;

Mail::fake();

Mail::assertSent(WelcomeEmail::class);
```

## Pagination

List endpoints return a response with a `PaginationInfo` object:

```php
$list = LetMeSendEmail::emails()->list(perPage: 10);

// Items on this page
$emails = $list->items();

// Pagination metadata
$pag = $list->pagination();
$pag->hasMore();   // bool
$pag->getTotal();  // int
$pag->getPerPage(); // int

// Next page: use the last item's ID
if ($pag->hasMore() && count($list->items()) > 0) {
    $items = $list->items();
    $lastId = $items[count($items) - 1]->getId();
    $nextPage = LetMeSendEmail::emails()->list(perPage: 10, after: $lastId);
}

// Previous page: use the first item's ID (from a page other than the first)
if (count($list->items()) > 0) {
    $items = $list->items();
    $firstId = $items[0]->getId();
    $prevPage = LetMeSendEmail::emails()->list(perPage: 10, before: $firstId);
}
```

Never pass `after` and `before` together.

The same `perPage`, `after`, and `before` parameters apply to all other list methods:

```php
// Domains
LetMeSendEmail::domains()->list(perPage: 20, after: $lastDomainId);
LetMeSendEmail::domains()->list(perPage: 20, before: $firstDomainId);

// Contacts
LetMeSendEmail::contacts()->list(perPage: 10, after: $lastContactId);
LetMeSendEmail::contacts()->list(perPage: 10, before: $firstContactId);

// Contact Categories
LetMeSendEmail::contactCategories()->list(perPage: 20, after: $lastCategoryId);
LetMeSendEmail::contactCategories()->list(perPage: 20, before: $firstCategoryId);

// Email Topics
LetMeSendEmail::emailTopics()->list(perPage: 20, after: $lastTopicId);
LetMeSendEmail::emailTopics()->list(perPage: 20, before: $firstTopicId);
```



## Errors and Exceptions

All API errors throw exceptions from the PHP SDK that extend
`LetMeSendEmail\Exceptions\ApiException`:

| Exception | HTTP Status | Description |
|-----------|-------------|-------------|
| `ValidationError` | 400, 413, 422 | Request validation failed |
| `AuthenticationError` | 401 | Invalid or missing API key |
| `AuthorizationError` | 403 | Insufficient permissions |
| `NotFoundError` | 404 | Resource not found |
| `ConflictError` | 409 | Resource conflict |
| `RateLimitError` | 429 | Rate limit exceeded |
| `ApiError` | 500+ | Server error |
| `NetworkError` | — | Connection failed |
| `TimeoutError` | — | Request timed out |

### Error Metadata

Every `ApiException` provides:

- `getMessage()` — error description
- `getHttpStatus()` — HTTP status code
- `getApiCode()` — API error code (e.g. `domain_not_found`)
- `getValidationErrors()` — field-level validation errors
- `getHeaders()` — response headers
- `getRequestId()` — request ID for debugging
- `getRawBody()` — raw response body

`RateLimitError` additionally provides `getRetryAfter()`, `getLimit()`,
`getRemaining()`, `getResetAt()`.

### Error Handling Example

```php
use LetMeSendEmail\Exceptions\ValidationError;
use LetMeSendEmail\Exceptions\AuthenticationError;
use LetMeSendEmail\Exceptions\RateLimitError;
use LetMeSendEmail\Exceptions\ApiException;

try {
    LetMeSendEmail::emails()->send(/* ... */);
} catch (ValidationError $e) {
    print_r($e->getValidationErrors());
} catch (AuthenticationError $e) {
    echo 'Check your API key.';
} catch (RateLimitError $e) {
    echo 'Retry after ' . $e->getRetryAfter() . ' seconds.';
} catch (ApiException $e) {
    echo 'HTTP ' . $e->getHttpStatus() . ': ' . $e->getMessage();
}
```

## Timeouts, Cancellation, and Retries

### Timeout

The default timeout is 30 seconds. Configure via `LETMESENDEMAIL_TIMEOUT` or the
`timeout` constructor parameter.

### Retries

The SDK can automatically retry idempotent requests on transient failures:

```php
// config/letmesendemail.php
'retries' => env('LETMESENDEMAIL_RETRIES', 3),
```

**Eligibility:** GET, HEAD, OPTIONS, DELETE requests are always retryable.
POST, PUT, PATCH requests are retryable only when an `Idempotency-Key` header
is present.

**Retryable failures:** Network errors, timeouts, HTTP 408, 429, 500, 502, 503, 504.

**Backoff:** Bounded exponential backoff with jitter (75%–125% of base). Base
delay starts at 100ms and doubles each attempt. Capped at 300 seconds.

**Rate-limit (429):** Uses the exact `Retry-After` header value (delta-seconds
or HTTP-date). Missing, invalid, zero, or excessive (>300s) values cause the
error to be thrown immediately with no retry.

## Webhooks

### Configuration

```env
LETMESENDEMAIL_WEBHOOKS_ENABLED=true
LETMESENDEMAIL_WEBHOOK_SECRET=whsec_your_signing_secret
```

The route is registered at `/webhooks/letmesendemail` by default, protected by
the `VerifyWebhookSignature` middleware (aliased as `letmesendemail.webhook`).

### How It Works

1. The middleware reads the raw request body and webhook headers, calls
   `WebhookSignature::verify()`, and stores the verified payload on the request.
2. If the signature is invalid, the middleware returns a 400 response.
3. The controller reads the verified payload and dispatches
   `LetMeSendEmail\Laravel\Events\WebhookReceived`.

### Listening for Webhooks

```php
namespace App\Listeners;

use LetMeSendEmail\Laravel\Events\WebhookReceived;

class HandleLetMeSendEmailWebhook
{
    public function handle(WebhookReceived $event): void
    {
        match ($event->payload['event'] ?? '') {
            'email.delivered' => // handle delivery
            'email.bounced'   => // handle bounce
            default           => // unknown event
        };
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    \LetMeSendEmail\Laravel\Events\WebhookReceived::class => [
        \App\Listeners\HandleLetMeSendEmailWebhook::class,
    ],
];
```

### Timestamp Tolerance

The default tolerance is 300 seconds (5 minutes). Configure in config:

```php
// config/letmesendemail.php
'webhooks' => [
    'tolerance' => 60,
],
```

## Response Models

Every response object provides getter methods for its fields and a `toArray()`
method returning the data as a plain associative array with snake_case keys:

```php
$email = LetMeSendEmail::emails()->get('01kvv5dv472evp42a60sy4p7zx');

// Object access via getters
echo $email->getStatus();

// Array access via toArray()
$data = $email->toArray();
echo $data['status'];
print_r($data['recipients']);
```

The `toArray()` method is available on all response models from the underlying
PHP SDK, including `SendEmailResponse`, `VerifyEmailResponse`, `EmailListResponse`,
`ShowEmailResponse`, `ContactResponse`, `DomainResponse`, `ContactCategoryResponse`,
`EmailTopicResponse`, `RecipientResponse`, `EmailAttachmentResponse`,
`PaginationInfo`, `StatusResponse`, and `ContactUpdateResponse`.

## Testing

```bash
composer install
vendor/bin/pest
```

### Mail::fake

```php
use Illuminate\Support\Facades\Mail;

Mail::fake();

Mail::assertSent(WelcomeEmail::class);
```

### RecordingTransport

For testing the Facade and API resources without network calls, inject a
`RecordingTransport`:

```php
use LetMeSendEmail\Laravel\LetMeSendEmail;
use LetMeSendEmail\Laravel\Tests\Fakes\RecordingTransport;

$transport = new RecordingTransport();
$transport->responses = [
    ['status' => 200, 'headers' => [], 'body' => ['id' => 'email_123', 'status' => 'accepted']],
];

$client = new LetMeSendEmail(apiKey: 'test', transport: $transport);
// Use $client normally — assertions can inspect $transport->requests
```

## Runtime Support

| PHP | Laravel | Supported |
|-----|---------|-----------|
| 8.1 | 10 | Yes |
| 8.2 | 11 | Yes |
| 8.3 | 12 | Yes |
| 8.4+ | 13 | Yes |

## Upgrading

### From 0.1.x to 0.2.x

- The wrapper now accepts `retries`, `TransportInterface`, and preconfigured
  `Client` parameters.
- Retry configuration is read from `LETMESENDEMAIL_RETRIES` / config.
- The mail transport filters structural MIME headers case-insensitively.
- Webhook verification middleware stores payload via `$request->merge()`.
- The route uses `letmesendemail.webhook` middleware alias.
- `composer.lock` was removed from version control.

No migration guide is currently required for the current version. See the
[changelog](https://github.com/letmesendemail/letmesendemail-laravel/blob/master/CHANGELOG.md) for all changes.

## Getting Help

- [API Documentation](https://letmesend.email/docs)
- [GitHub Repository](https://github.com/letmesendemail/letmesendemail-laravel)
- [Issue Tracker](https://github.com/letmesendemail/letmesendemail-laravel/issues)
- [Changelog](../CHANGELOG.md)
