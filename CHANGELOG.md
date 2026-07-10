# Changelog

## Unreleased

- Reworked Laravel wrapper to accept `retries`, optional `TransportInterface`, or preconfigured
  core `Client` for testability and multi-tenant use.
- Added retry configuration support. Retry count is read from `LETMESENDEMAIL_RETRIES` / config
  and passed to the core PHP SDK `Configuration`.
- Replaced network-dependent mail transport tests with a `RecordingTransport` fake that asserts
  HTTP method, canonical URL, auth headers, User-Agent, request body, custom headers,
  Idempotency-Key, address formatting, and attachment serialization without live API calls.
- Mail transport now filters structural MIME headers (From, To, Cc, Bcc, Reply-To, Subject,
  Content-Type, MIME-Version, Date, Message-ID, Sender, Return-Path) from custom headers using
  case-insensitive comparison.
- Mail transport detects `Idempotency-Key` case-insensitively and passes it through the core
  `idempotencyKey` argument.
- Mail transport converts attachments through the core `Attachment` request API.
- Mail transport validates missing sender/recipient with `TransportException`.
- Mail transport maps `ApiException` to `TransportException`.
- Mail transport calls `$message->setMessageId($response->getId())` so the Symfony message ID
  matches the letmesend.email response ID.
- Webhook verification middleware stores the verified parsed payload on `$request->attributes`.
- Webhook controller reads the verified payload from request attributes and dispatches
  `WebhookReceived`.
- Webhook route uses `letmesendemail.webhook` middleware (aliased `VerifyWebhookSignature`).
- Added configurable webhook timestamp tolerance with 300-second default.
- Added PHPStan static analysis configuration with Composer scripts.
- Removed `composer.lock` from version control — this is a Composer library, resolved
  independently per project.
- Added CI matrix strategy covering Laravel 10–13 with PHP 8.1–8.5.
- Each CI job resolves dependencies independently with `composer update` to avoid lockfile
  conflicts across PHP/Laravel versions.
- Changed core PHP SDK constraint to `^0.2` only.
- Full README rewrite covering facade, config, mail transport, webhooks, and testing.
- Rewrote `publish-guide.md` for standalone repository root (no monorepo paths).

## 0.1.0 — 2026-07-09

- Initial release.
- Laravel service provider with auto-discovery.
- Config file publishable via `php artisan vendor:publish --tag=letmesendemail-config`.
- Facade for quick access.
- Emails, Domains, Contacts, Contact Categories, Email Topics resources.
- Uses the core PHP SDK under the hood.
