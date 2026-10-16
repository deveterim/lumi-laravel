<?php

declare(strict_types=1);

namespace Lumi\Laravel\Tests;

use Lumi\Laravel\Client\LumiClient;
use Lumi\Laravel\Transport\LumiTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class LumiTransportTest extends TestCase
{
    public function testSendEmailGeneratesCorrectPayload(): void
    {
        $mockClient = $this->createMock(LumiClient::class);
        $mockClient->expects($this->once())
            ->method('sendEmail')
            ->with($this->callback(function (array $payload) {
                return $payload['from'] === 'Lumi <sender@mailifica.com>'
                    && $payload['to'] === 'receiver@example.com'
                    && $payload['subject'] === 'Welcome to Lumi'
                    && str_contains($payload['html'], '<h1>Welcome</h1>')
                    && $payload['reply_to'] === 'support@mailifica.com'
                    && isset($payload['headers']['X-Custom-Header'])
                    && $payload['tags'][0]['name'] === 'category'
                    && $payload['tags'][0]['value'] === 'onboarding';
            }))
            ->willReturn(['id' => 'email_12345', 'status' => 'queued']);

        $transport = new LumiTransport($mockClient);

        $email = (new Email())
            ->from(new Address('sender@mailifica.com', 'Lumi'))
            ->to(new Address('receiver@example.com'))
            ->replyTo(new Address('support@mailifica.com'))
            ->subject('Welcome to Lumi')
            ->html('<h1>Welcome</h1>')
            ->text('Welcome');

        $email->getHeaders()->addTextHeader('X-Custom-Header', 'custom_value');
        $email->getHeaders()->addTextHeader('X-Lumi-Tag', 'category=onboarding');

        $envelope = Envelope::create($email);
        $sentMessage = new SentMessage($email, $envelope);

        $transport->send($email, $envelope);
        $this->assertEquals('mailifica', (string) $transport);
    }
}
