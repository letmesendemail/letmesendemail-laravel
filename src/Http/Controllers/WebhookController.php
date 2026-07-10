<?php

declare(strict_types=1);

namespace LetMeSendEmail\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LetMeSendEmail\Laravel\Events\WebhookReceived;

final class WebhookController extends Controller
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = $request->attributes->get('letmesendemail_webhook_payload');

        if (!is_array($payload)) {
            return response()->json(['error' => 'Webhook payload not verified.'], 400);
        }

        WebhookReceived::dispatch($payload);

        return response()->json(['received' => true]);
    }
}
