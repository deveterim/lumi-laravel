<?php

declare(strict_types=1);

namespace Mailifica\Laravel\Notifications;

use Illuminate\Notifications\Notification;
use Mailifica\Laravel\Client\MailificaClient;
use Mailifica\Laravel\Notifications\Messages\MailificaMessage;

class MailificaChannel
{
    protected MailificaClient $client;

    public function __construct(MailificaClient $client)
    {
        $this->client = $client;
    }

    /**
     * Send the given notification.
     */
    public function send(mixed $notifiable, Notification $notification): ?array
    {
        if (!method_exists($notification, 'toMailifica')) {
            return null;
        }

        /** @var MailificaMessage|array $message */
        $message = $notification->toMailifica($notifiable);

        if (is_array($message)) {
            $payload = $message;
        } elseif ($message instanceof MailificaMessage) {
            $payload = $message->toArray();
        } else {
            return null;
        }

        // Auto-assign recipient if not set explicitly
        if (empty($payload['to'])) {
            if ($to = $notifiable->routeNotificationFor('mailifica', $notification)) {
                $payload['to'] = $to;
            } elseif ($to = $notifiable->routeNotificationFor('mail', $notification)) {
                $payload['to'] = $to;
            } elseif (isset($notifiable->email)) {
                $payload['to'] = $notifiable->email;
            }
        }

        if (empty($payload['to'])) {
            return null;
        }

        return $this->client->sendEmail($payload);
    }
}
