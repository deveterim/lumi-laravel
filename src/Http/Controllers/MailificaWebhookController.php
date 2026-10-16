<?php

declare(strict_types=1);

namespace Mailifica\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mailifica\Laravel\Client\MailificaClient;
use Mailifica\Laravel\Events\MailificaWebhookReceived;
use Mailifica\Laravel\Exceptions\WebhookVerificationException;

class MailificaWebhookController extends Controller
{
    protected MailificaClient $client;

    public function __construct(MailificaClient $client)
    {
        $this->client = $client;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('mailifica.webhook.secret');
        $signatureHeader = $request->header('Mailifica-Signature') ?: $request->header('X-Mailifica-Signature');

        if (!empty($secret)) {
            if (empty($signatureHeader)) {
                return response()->json(['error' => 'Missing webhook signature header'], 400);
            }

            $rawPayload = $request->getContent();
            $tolerance = (int) config('mailifica.webhook.tolerance', 300);

            try {
                $this->client->verifyWebhookSignature($rawPayload, $signatureHeader, $secret, $tolerance);
            } catch (WebhookVerificationException $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        $payload = $request->json()->all();
        $eventType = $payload['type'] ?? ($payload['event'] ?? 'unknown');

        event(new MailificaWebhookReceived($eventType, $payload));

        return response()->json(['status' => 'success', 'event' => $eventType]);
    }
}
