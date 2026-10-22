<?php

declare(strict_types=1);

namespace Lumi\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LumiWebhookReceived
{
    use Dispatchable, SerializesModels;

    public string $type;
    public array $payload;
    public ?array $data;

    public function __construct(string $type, array $payload)
    {
        $this->type = $type;
        $this->payload = $payload;
        $this->data = $payload['data'] ?? $payload;
    }
}
