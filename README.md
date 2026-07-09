# letmesend.email SDK for Laravel
[![Packagist Downloads](https://img.shields.io/packagist/dt/letmesendemail/letmesendemail-laravel?style=for-the-badge&labelColor=000000)](https://packagist.org/packages/letmesendemail/letmesendemail-laravel)
[![Packagist Version](https://img.shields.io/packagist/v/letmesendemail/letmesendemail-laravel?style=for-the-badge&labelColor=000000)](https://packagist.org/packages/letmesendemail/letmesendemail-laravel)
[![License](https://img.shields.io/github/license/letmesendemail/letmesendemail-laravel?color=9cf&style=for-the-badge&labelColor=000000&cache=v1)](https://github.com/letmesendemail/letmesendemail-php/blob/master/LICENSE)

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

Optionally configure the base URL and timeout:

```env
LETMESENDEMAIL_BASE_URL=https://letmesend.email/api/v1
LETMESENDEMAIL_TIMEOUT=30
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag=letmesendemail-config
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

// List emails
$list = LetMeSendEmail::emails()->list(perPage: 20);

foreach ($list->items() as $email) {
    echo $email->getId() . ' - ' . $email->getSubject();
}

echo $list->pagination()->hasMore();

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
$contact = LetMeSendEmail::contacts()->update($id, firstName: 'Jane');
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

## Error Handling

```php
use LetMeSendEmail\Exceptions\ValidationError;
use LetMeSendEmail\Exceptions\AuthenticationError;
use LetMeSendEmail\Exceptions\ApiException;

try {
    LetMeSendEmail::emails()->send(/* ... */);
} catch (ValidationError $e) {
    // field-level errors: $e->getValidationErrors()
} catch (AuthenticationError $e) {
    // check API key
} catch (ApiException $e) {
    // generic API error
}
```

## Testing

```bash
composer install
vendor/bin/pest
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
