<?php

declare(strict_types=1);

namespace LetMeSendEmail\Laravel\Tests\Fakes;

use LetMeSendEmail\Http\TransportInterface;

final class RecordingTransport implements TransportInterface
{
    /** @var array<int, array{method: string, uri: string, options: array}> */
    public array $requests = [];

    public array $responses = [];

    public function request(string $method, string $uri, array $options = []): array
    {
        $this->requests[] = [
            'method' => $method,
            'uri' => $uri,
            'options' => $options,
        ];

        $response = array_shift($this->responses);

        if ($response !== null) {
            return $response;
        }

        return ['status' => 200, 'headers' => [], 'body' => []];
    }
}
