# letmesend.email SDK for Laravel
[![Packagist Downloads](https://img.shields.io/packagist/dt/letmesendemail/letmesendemail-laravel?style=for-the-badge&labelColor=000000)](https://packagist.org/packages/letmesendemail/letmesendemail-laravel)
[![Packagist Version](https://img.shields.io/packagist/v/letmesendemail/letmesendemail-laravel?style=for-the-badge&labelColor=000000)](https://packagist.org/packages/letmesendemail/letmesendemail-laravel)
[![License](https://img.shields.io/github/license/letmesendemail/letmesendemail-laravel?color=9cf&style=for-the-badge&labelColor=000000&cache=v1)](LICENSE.md)

The official Laravel package for the [letmesend.email](https://letmesend.email/) API.

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12, or 13

## Installation

```bash
composer require letmesendemail/letmesendemail-laravel
```

## Configuration

Set your API key in `.env`:

```env
LETMESENDEMAIL_API_KEY=lms_live_...
```

Optionally configure the base URL, timeout, and retries:

```env
LETMESENDEMAIL_BASE_URL=https://letmesend.email/api/v1
LETMESENDEMAIL_TIMEOUT=30
LETMESENDEMAIL_RETRIES=3
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag=letmesendemail-config
```

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `LETMESENDEMAIL_API_KEY` | — | Your letmesend.email API key |
| `LETMESENDEMAIL_BASE_URL` | `https://letmesend.email/api/v1` | API base URL |
| `LETMESENDEMAIL_TIMEOUT` | `30` | Request timeout in seconds |
| `LETMESENDEMAIL_RETRIES` | `0` | Retry attempts for transient failures |
| `LETMESENDEMAIL_WEBHOOK_SECRET` | — | Webhook signing secret |
| `LETMESENDEMAIL_WEBHOOKS_ENABLED` | `false` | Enable webhook route |
| `LETMESENDEMAIL_WEBHOOK_PATH` | `/webhooks/letmesendemail` | Webhook URI path |

### Explicit Configuration

For tests and multi-tenant applications, configure the client explicitly:

```php
use LetMeSendEmail\Laravel\LetMeSendEmail;

$client = new LetMeSendEmail(
    apiKey: 'lms_live_...',
    baseUrl: 'https://letmesend.email/api/v1',
    timeout: 60,
    retries: 5,
);
```

You may also inject a preconfigured core `Client` or a `TransportInterface` for testing:

```php
use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Http\GuzzleTransport;
use GuzzleHttp\Client as GuzzleClient;

$httpClient = new LetMeSendEmail(
    client: new Client(
        new Configuration(apiKey: '...', retries: 5),
        new GuzzleTransport(new GuzzleClient()),
    ),
);
```

## Usage

### Facade

```php
use LetMeSendEmail\Laravel\Facades\LetMeSendEmail;
```

### Emails

```php
// Send
$email = LetMeSendEmail::emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    subject: 'Welcome',
    html: '<p>Hello from letmesend.email</p>',
);

echo $email->getId();

// Send with template
$email = LetMeSendEmail::emails()->sendWithTemplate(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    templateId: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
    templateVariables: [
        ['key' => 'USER_NAME', 'type' => 'string', 'value' => 'John'],
    ],
);

// Verify email
$result = LetMeSendEmail::emails()->verify('person@example.com');
echo $result->getStatus();

// List emails (cursor-based pagination)
$list = LetMeSendEmail::emails()->list(perPage: 20);

foreach ($list->items() as $email) {
    echo $email->getId() . ' - ' . $email->getSubject();
}

echo $list->pagination()->hasMore(); // true

// Next page
$list = LetMeSendEmail::emails()->list(perPage: 20, after: 'cursor_from_previous_page');

// Get email
$email = LetMeSendEmail::emails()->get('01kvv5dv472evp42a60sy4p7zx');
```

### Domains

```php
$list = LetMeSendEmail::domains()->list();
$domain = LetMeSendEmail::domains()->get($id);
$result = LetMeSendEmail::domains()->verify('example.com');
```

### Contacts

```php
$contact = LetMeSendEmail::contacts()->create(
    email: 'john@example.com',
    firstName: 'John',
    lastName: 'Doe',
);

$list = LetMeSendEmail::contacts()->list();
$contact = LetMeSendEmail::contacts()->get($id);
$updated = LetMeSendEmail::contacts()->update($id, firstName: 'Jane');
$result = LetMeSendEmail::contacts()->delete($id);
```

### Contact Categories

```php
$category = LetMeSendEmail::contactCategories()->create(name: 'New Name');
$list = LetMeSendEmail::contactCategories()->list();
$category = LetMeSendEmail::contactCategories()->get($id);
$category = LetMeSendEmail::contactCategories()->update($id, name: 'Updated');
$result = LetMeSendEmail::contactCategories()->delete($id);
```

### Email Topics

```php
$topic = LetMeSendEmail::emailTopics()->create(
    name: 'Product Updates',
    slug: 'product-updates',
);

$list = LetMeSendEmail::emailTopics()->list();
$topic = LetMeSendEmail::emailTopics()->get($id);
$topic = LetMeSendEmail::emailTopics()->update($id, name: 'Updated');
$result = LetMeSendEmail::emailTopics()->delete($id);
```

## Laravel Mail Transport

Send emails through Laravel's mail system using the `letmesendemail` mailer.

### Configuration

Set your `.env` mailer:

```env
MAIL_MAILER=letmesendemail
```

Or configure `config/mail.php`:

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
use Illuminate\Mail\Mailables\Attachment;

$this->attachFromStorage('/path/to/report.pdf');
// or
$this->attachData('file content', 'report.txt', ['mime' => 'text/plain']);
```

Structural MIME headers (From, To, Cc, Bcc, Reply-To, Subject, Content-Type, MIME-Version,
Date, Message-ID, Sender, Return-Path) are automatically excluded from the API custom headers.

### Idempotency

Set an `Idempotency-Key` header on the Mailable:

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

The SDK detects `Idempotency-Key` case-insensitively and passes it through the core API's
`idempotencyKey` parameter.

### Queue

```php
Mail::to('user@example.com')->queue(new WelcomeEmail());
```

The transport's `ApiException` mapping to `Symfony TransportException` works in queued jobs.

### Testing

```php
use Illuminate\Support\Facades\Mail;

Mail::fake();

Mail::assertSent(WelcomeEmail::class);
```

## Pagination

List endpoints return a response with cursor-based pagination:

```php
$list = LetMeSendEmail::emails()->list(perPage: 10);

foreach ($list->items() as $email) {
    echo $email->getId();
}

$pag = $list->pagination();
$pag->hasMore();   // bool
$pag->getTotal();  // int
$pag->getPerPage(); // int

// Next page
$next = LetMeSendEmail::emails()->list(perPage: 10, after: 'cursor_value');

// Previous page
$prev = LetMeSendEmail::emails()->list(perPage: 10, before: 'cursor_value');
```

## Error Handling

```php
use LetMeSendEmail\Exceptions\ValidationError;
use LetMeSendEmail\Exceptions\AuthenticationError;
use LetMeSendEmail\Exceptions\RateLimitError;
use LetMeSendEmail\Exceptions\ApiException;

try {
    LetMeSendEmail::emails()->send(/* ... */);
} catch (ValidationError $e) {
    // field-level errors: $e->getValidationErrors()
} catch (AuthenticationError $e) {
    // check API key
} catch (RateLimitError $e) {
    // retry after $e->getRetryAfter()
} catch (ApiException $e) {
    // HTTP status: $e->getHttpStatus()
    // API code: $e->getApiCode()
}
```

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

## Webhooks

### Configuration

Enable webhooks in your `.env`:

```env
LETMESENDEMAIL_WEBHOOKS_ENABLED=true
LETMESENDEMAIL_WEBHOOK_SECRET=whsec_your_signing_secret
```

The webhook route is registered at `/webhooks/letmesendemail` by default. It uses
the `VerifyWebhookSignature` middleware (aliased as `letmesendemail.webhook`) which
verifies the signature before the controller executes.

### How it works

1. The middleware reads the raw request body and webhook headers, calls
   `WebhookSignature::verify()`, and stores the parsed payload on the request.
2. If the signature is invalid, the middleware returns a 400 response.
3. The controller reads the verified payload from the request and dispatches
   `LetMeSendEmail\Laravel\Events\WebhookReceived`.

### Listening for webhooks

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

Register the listener in `EventServiceProvider`:

```php
protected $listen = [
    \LetMeSendEmail\Laravel\Events\WebhookReceived::class => [
        \App\Listeners\HandleLetMeSendEmailWebhook::class,
    ],
];
```

### Timestamp tolerance

The default tolerance is 300 seconds (5 minutes). Configure via config:

```php
// config/letmesendemail.php
'webhooks' => [
    'tolerance' => 300,
],
```

## Testing

```bash
composer install
vendor/bin/pest
```

### Mail::fake with the letmesendemail transport

```php
use Illuminate\Support\Facades\Mail;

Mail::fake();

Mail::assertSent(WelcomeEmail::class);
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
