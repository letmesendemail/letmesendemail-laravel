<?php

declare(strict_types=1);

namespace LetMeSendEmail\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class WebhookReceived
{
    use Dispatchable;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly array $payload,
    ) {
    }
}
