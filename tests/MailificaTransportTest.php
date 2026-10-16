<?php

declare(strict_types=1);

namespace Mailifica\Laravel\Tests;

use Mailifica\Laravel\Client\MailificaClient;
use Mailifica\Laravel\Transport\MailificaTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailificaTransportTest extends TestCase
{
    public function testSendEmailGeneratesCorrectPayload(): void
    {
        $mockClient = $this->createMock(MailificaClient::class);
        $mockClient->expects($this->once())
            ->method('sendEmail')
            ->with($this->callback(function (array $payload) {
                return $payload['from'] === 'Mailifica <sender@mailifica.com>'
                    && $payload['to'] === 'receiver@example.com'
                    && $payload['subject'] === 'Welcome to Mailifica'
                    && str_contains($payload['html'], '<h1>Welcome</h1>')
                    && $payload['reply_to'] === 'support@mailifica.com'
                    && isset($payload['headers']['X-Custom-Header'])
                    && $payload['tags'][0]['name'] === 'category'
                    && $payload['tags'][0]['value'] === 'onboarding';
            }))
            ->willReturn(['id' => 'email_12345', 'status' => 'queued']);

        $transport = new MailificaTransport($mockClient);

        $email = (new Email())
            ->from(new Address('sender@mailifica.com', 'Mailifica'))
            ->to(new Address('receiver@example.com'))
            ->replyTo(new Address('support@mailifica.com'))
            ->subject('Welcome to Mailifica')
            ->html('<h1>Welcome</h1>')
            ->text('Welcome');

        $email->getHeaders()->addTextHeader('X-Custom-Header', 'custom_value');
        $email->getHeaders()->addTextHeader('X-Mailifica-Tag', 'category=onboarding');

        $envelope = Envelope::create($email);
        $sentMessage = new SentMessage($email, $envelope);

        $transport->send($email, $envelope);
        $this->assertEquals('mailifica', (string) $transport);
    }
}
