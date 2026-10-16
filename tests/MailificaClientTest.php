<?php

declare(strict_types=1);

namespace Mailifica\Laravel\Tests;

use Mailifica\Laravel\Client\MailificaClient;
use Mailifica\Laravel\Exceptions\WebhookVerificationException;
use PHPUnit\Framework\TestCase;

class MailificaClientTest extends TestCase
{
    public function testVerifyWebhookSignatureValid(): void
    {
        $client = new MailificaClient('ma_test_key');
        $secret = 'whsec_test_secret_key_123';
        $payload = json_encode(['event' => 'email.delivered', 'id' => 'evt_123']);
        $timestamp = time();

        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        $header = "t={$timestamp},v1={$signature}";

        $this->assertTrue($client->verifyWebhookSignature($payload, $header, $secret, 300));
    }

    public function testVerifyWebhookSignatureInvalidFails(): void
    {
        $this->expectException(WebhookVerificationException::class);

        $client = new MailificaClient('ma_test_key');
        $secret = 'whsec_test_secret_key_123';
        $payload = json_encode(['event' => 'email.bounced']);
        $header = "t=" . time() . ",v1=invalid_signature_hash";

        $client->verifyWebhookSignature($payload, $header, $secret, 300);
    }
}
