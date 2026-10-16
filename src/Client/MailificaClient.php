<?php

declare(strict_types=1);

namespace Mailifica\Laravel\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Mailifica\Laravel\Exceptions\MailificaException;
use Mailifica\Laravel\Exceptions\WebhookVerificationException;

class MailificaClient
{
    public const VERSION = '1.0.1';

    protected string $apiKey;
    protected string $baseUrl;
    protected GuzzleClient $httpClient;

    public function __construct(string $apiKey, ?string $baseUrl = null, ?GuzzleClient $httpClient = null, float $timeout = 30.0)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl ?: 'https://api.mailifica.com/v1', '/');
        $this->httpClient = $httpClient ?: new GuzzleClient([
            'base_uri' => $this->baseUrl,
            'timeout'  => $timeout,
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'User-Agent'    => 'mailifica-laravel/' . self::VERSION,
            ],
        ]);
    }

    /**
     * Send raw HTTP request to Mailifica API.
     *
     * @throws MailificaException
     */
    public function request(string $method, string $uri, array $options = []): array
    {
        try {
            $uri = '/' . ltrim($uri, '/');
            $response = $this->httpClient->request($method, $this->baseUrl . $uri, $options);
            $body = (string) $response->getBody();

            if (empty($body)) {
                return [];
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['raw' => $body];
            }

            return $data;
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = null;
            $errorMessage = $e->getMessage();

            if ($e->hasResponse()) {
                $rawBody = (string) $e->getResponse()->getBody();
                $decoded = json_decode($rawBody, true);
                if (is_array($decoded)) {
                    $responseBody = $decoded;
                    $msg = $decoded['message'] ?? $decoded['error'] ?? null;
                    if (is_string($msg)) {
                        $errorMessage = $msg;
                    } elseif (is_array($msg)) {
                        $errorMessage = isset($msg['message']) && is_string($msg['message'])
                            ? $msg['message']
                            : json_encode($msg);
                    }
                }
            }

            throw new MailificaException($errorMessage, $statusCode, $responseBody, $e);
        } catch (GuzzleException $e) {
            throw new MailificaException($e->getMessage(), 0, null, $e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Emails
    |--------------------------------------------------------------------------
    */

    public function sendEmail(array $payload): array
    {
        if (isset($payload['to']) && is_array($payload['to'])) {
            $payload['to'] = count($payload['to']) > 0 ? $payload['to'][0] : '';
        }
        if (isset($payload['reply_to']) && is_array($payload['reply_to'])) {
            $payload['reply_to'] = count($payload['reply_to']) > 0 ? $payload['reply_to'][0] : '';
        }
        if (isset($payload['tags']) && is_array($payload['tags'])) {
            $normalizedTags = [];
            foreach ($payload['tags'] as $tag) {
                if (is_array($tag) && isset($tag['name'])) {
                    $normalizedTags[] = isset($tag['value']) ? $tag['name'] . ':' . $tag['value'] : $tag['name'];
                } elseif (is_string($tag)) {
                    $normalizedTags[] = $tag;
                }
            }
            $payload['tags'] = $normalizedTags;
        }

        return $this->request('POST', '/emails', ['json' => $payload]);
    }

    public function getEmail(string $id): array
    {
        return $this->request('GET', '/emails/' . $id);
    }

    public function listEmails(array $query = []): array
    {
        return $this->request('GET', '/emails', ['query' => $query]);
    }

    public function sendBatch(array $emails): array
    {
        return $this->request('POST', '/emails/batch', ['json' => ['emails' => $emails]]);
    }

    /*
    |--------------------------------------------------------------------------
    | Domains
    |--------------------------------------------------------------------------
    */

    public function listDomains(): array
    {
        return $this->request('GET', '/domains');
    }

    public function getDomain(string $id): array
    {
        return $this->request('GET', '/domains/' . $id);
    }

    public function createDomain(string $name): array
    {
        return $this->request('POST', '/domains', ['json' => ['name' => $name]]);
    }

    public function verifyDomain(string $id): array
    {
        return $this->request('POST', '/domains/' . $id . '/verify');
    }

    public function deleteDomain(string $id): array
    {
        return $this->request('DELETE', '/domains/' . $id);
    }

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    */

    public function listTemplates(): array
    {
        return $this->request('GET', '/templates');
    }

    public function getTemplate(string $id): array
    {
        return $this->request('GET', '/templates/' . $id);
    }

    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    */

    public function listApiKeys(): array
    {
        return $this->request('GET', '/api-keys');
    }

    public function createApiKey(string $name, ?string $permission = 'full_access'): array
    {
        return $this->request('POST', '/api-keys', ['json' => [
            'name' => $name,
            'permission' => $permission,
        ]]);
    }

    public function deleteApiKey(string $id): array
    {
        return $this->request('DELETE', '/api-keys/' . $id);
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks Signature Verification
    |--------------------------------------------------------------------------
    */

    /**
     * Verify the HMAC-SHA256 signature of an incoming Mailifica webhook.
     *
     * @throws WebhookVerificationException
     */
    public function verifyWebhookSignature(string $payload, string $header, string $secret, int $tolerance = 300): bool
    {
        if (empty($secret)) {
            throw new WebhookVerificationException('Webhook secret is not configured.');
        }

        // Header format expected: t=1614555555,v1=signature_hash OR raw signature
        $timestamp = null;
        $signature = null;

        if (str_contains($header, ',')) {
            $parts = explode(',', $header);
            foreach ($parts as $part) {
                $kv = explode('=', trim($part), 2);
                if (count($kv) === 2) {
                    if ($kv[0] === 't') {
                        $timestamp = (int) $kv[1];
                    } elseif ($kv[0] === 'v1') {
                        $signature = $kv[1];
                    }
                }
            }
        } else {
            $signature = $header;
        }

        if ($timestamp !== null && $tolerance > 0) {
            $now = time();
            if (abs($now - $timestamp) > $tolerance) {
                throw new WebhookVerificationException("Webhook timestamp is too old or in the future ($timestamp vs $now).");
            }
            $signedPayload = $timestamp . '.' . $payload;
        } else {
            $signedPayload = $payload;
        }

        $expected = hash_hmac('sha256', $signedPayload, $secret);

        if (!hash_equals($expected, $signature)) {
            // Also try fallback signature comparison without timestamp
            $fallbackExpected = hash_hmac('sha256', $payload, $secret);
            if (!hash_equals($fallbackExpected, $signature)) {
                throw new WebhookVerificationException('Invalid webhook signature.');
            }
        }

        return true;
    }
}
