<?php

declare(strict_types=1);

namespace Lumi\Laravel\Notifications;

use Illuminate\Notifications\Notification;
use Lumi\Laravel\Client\LumiClient;
use Lumi\Laravel\Notifications\Messages\LumiMessage;

class LumiChannel
{
    protected LumiClient $client;

    public function __construct(LumiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Send the given notification.
     */
    public function send(mixed $notifiable, Notification $notification): ?array
    {
        if (!method_exists($notification, 'toLumi')) {
            return null;
        }

        /** @var LumiMessage|array $message */
        $message = $notification->toLumi($notifiable);

        if (is_array($message)) {
            $payload = $message;
        } elseif ($message instanceof LumiMessage) {
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
