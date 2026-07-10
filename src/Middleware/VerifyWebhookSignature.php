<?php

declare(strict_types=1);

namespace LetMeSendEmail\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use LetMeSendEmail\Exceptions\WebhookVerificationException;
use LetMeSendEmail\Support\WebhookSignature;

final class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): mixed
    {
        $secret = config('letmesendemail.webhooks.signing_secret', '');

        if ($secret === '') {
            throw WebhookVerificationException::fromReason('Webhook signing secret is not configured.');
        }

        assert(method_exists($request, 'getContent'));
        $rawPayload = $request->getContent();

        $headers = [];
        foreach (['webhook-id', 'webhook-log-id', 'webhook-timestamp', 'webhook-signature'] as $name) {
            $value = $request->header($name);
            if ($value !== null) {
                $headers[$name] = $value;
            }
        }

        $tolerance = (int) config('letmesendemail.webhooks.tolerance', 300);

        try {
            $event = WebhookSignature::verify(
                payload: $rawPayload,
                headers: $headers,
                secret: $secret,
                tolerance: $tolerance,
            );
        } catch (WebhookVerificationException $e) {
            abort(400, 'Webhook verification failed: ' . $e->getMessage());
        }

        /** @phpstan-ignore-next-line (Access to undefined property) */
        $request->attributes->set('letmesendemail_webhook_payload', $event);

        return $next($request);
    }
}
