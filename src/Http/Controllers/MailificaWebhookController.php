<?php

declare(strict_types=1);

namespace Lumi\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lumi\Laravel\Client\LumiClient;
use Lumi\Laravel\Events\LumiWebhookReceived;
use Lumi\Laravel\Exceptions\WebhookVerificationException;

class LumiWebhookController extends Controller
{
    protected LumiClient $client;

    public function __construct(LumiClient $client)
    {
        $this->client = $client;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('lumi.webhook.secret');
        $signatureHeader = $request->header('Lumi-Signature') ?: $request->header('X-Lumi-Signature');

        if (!empty($secret)) {
            if (empty($signatureHeader)) {
                return response()->json(['error' => 'Missing webhook signature header'], 400);
            }

            $rawPayload = $request->getContent();
            $tolerance = (int) config('lumi.webhook.tolerance', 300);

            try {
                $this->client->verifyWebhookSignature($rawPayload, $signatureHeader, $secret, $tolerance);
            } catch (WebhookVerificationException $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        $payload = $request->json()->all();
        $eventType = $payload['type'] ?? ($payload['event'] ?? 'unknown');

        event(new LumiWebhookReceived($eventType, $payload));

        return response()->json(['status' => 'success', 'event' => $eventType]);
    }
}
