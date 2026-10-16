<?php

declare(strict_types=1);

namespace Lumi\Laravel\Transport;

use Lumi\Laravel\Client\LumiClient;
use Lumi\Laravel\Exceptions\LumiException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\RawMessage;

class LumiTransport extends AbstractTransport
{
    protected LumiClient $client;

    public function __construct(LumiClient $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope() ?? Envelope::create($email);

        $payload = $this->buildPayload($email, $envelope);

        try {
            $response = $this->client->sendEmail($payload);
            
            $messageId = $response['id'] ?? ($response['data']['id'] ?? null);
            if ($messageId) {
                $message->getOriginalMessage()->getHeaders()->addHeader('X-Lumi-Message-ID', (string) $messageId);
            }
        } catch (LumiException $e) {
            throw new \Symfony\Component\Mailer\Exception\TransportException(
                'Unable to send email via Lumi: ' . $e->getMessage(),
                $e->getStatusCode() ?? 0,
                $e
            );
        }
    }

    /**
     * Build the Lumi API JSON payload from Symfony Email.
     */
    protected function buildPayload(Email $email, Envelope $envelope): array
    {
        $from = $this->formatAddresses($email->getFrom());
        if (empty($from) && !empty($envelope->getSender())) {
            $from = [$this->formatAddress($envelope->getSender())];
        }

        $to = $this->formatAddresses($email->getTo());
        if (empty($to) && !empty($envelope->getRecipients())) {
            $to = $this->formatAddresses($envelope->getRecipients());
        }

        $payload = [
            'from' => $from[0] ?? '',
            'to' => $to,
            'subject' => $email->getSubject() ?? '',
        ];

        // Recipient string fallback if single recipient expected
        if (count($to) === 1) {
            $payload['to'] = $to[0];
        }

        if (!empty($email->getCc())) {
            $payload['cc'] = $this->formatAddresses($email->getCc());
        }

        if (!empty($email->getBcc())) {
            $payload['bcc'] = $this->formatAddresses($email->getBcc());
        }

        if (!empty($email->getReplyTo())) {
            $replyTo = $this->formatAddresses($email->getReplyTo());
            $payload['reply_to'] = $replyTo[0] ?? null;
        }

        if ($html = $email->getHtmlBody()) {
            $payload['html'] = is_resource($html) ? stream_get_contents($html) : (string) $html;
        }

        if ($text = $email->getTextBody()) {
            $payload['text'] = is_resource($text) ? stream_get_contents($text) : (string) $text;
        }

        // Handle Attachments
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $body = $attachment->getBody();
            $content = is_resource($body) ? stream_get_contents($body) : (string) $body;
            $attachments[] = [
                'filename' => $attachment->getFilename() ?: 'attachment',
                'content' => base64_encode($content),
                'content_type' => $attachment->getContentType(),
            ];
        }
        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        // Handle Custom Headers and Lumi-specific Headers
        $headers = [];
        $tags = [];
        $metadata = [];

        foreach ($email->getHeaders()->all() as $header) {
            $name = $header->getName();
            $body = $header->getBodyAsString();

            if (strcasecmp($name, 'X-Lumi-Tag') === 0 || strcasecmp($name, 'X-Tag') === 0) {
                if (str_contains($body, '=')) {
                    [$tagName, $tagVal] = explode('=', $body, 2);
                    $tags[] = ['name' => trim($tagName), 'value' => trim($tagVal)];
                } else {
                    $tags[] = ['name' => trim($body), 'value' => 'true'];
                }
            } elseif (strcasecmp($name, 'X-Lumi-Scheduled-At') === 0) {
                $payload['scheduled_at'] = trim($body);
            } elseif (strcasecmp($name, 'X-Lumi-Template') === 0) {
                $payload['template_id'] = trim($body);
            } elseif (strcasecmp($name, 'X-Lumi-Variables') === 0) {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    $payload['variables'] = $decoded;
                }
            } elseif (strcasecmp($name, 'X-Lumi-Metadata') === 0) {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    $metadata = array_merge($metadata, $decoded);
                }
            } elseif (!in_array(strtolower($name), ['from', 'to', 'cc', 'bcc', 'subject', 'reply-to', 'content-type', 'mime-version'], true)) {
                $headers[$name] = $body;
            }
        }

        if (!empty($headers)) {
            $payload['headers'] = $headers;
        }

        if (!empty($tags)) {
            $payload['tags'] = $tags;
        }

        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        return $payload;
    }

    /**
     * Format a list of Address objects into string array.
     */
    protected function formatAddresses(array $addresses): array
    {
        return array_map(function (Address $address) {
            return $this->formatAddress($address);
        }, $addresses);
    }

    /**
     * Format a single Address object.
     */
    protected function formatAddress(Address $address): string
    {
        if ($address->getName()) {
            return sprintf('%s <%s>', $address->getName(), $address->getAddress());
        }

        return $address->getAddress();
    }

    public function __toString(): string
    {
        return 'lumi';
    }
}
