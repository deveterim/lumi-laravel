# Mailifica Laravel Driver & SDK

> Official Laravel integration for the **Mailifica** Transactional & Marketing Email Infrastructure.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mailifica/mailifica-laravel.svg?style=flat-square)](https://packagist.org/packages/mailifica/mailifica-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/mailifica/mailifica-laravel.svg?style=flat-square)](https://packagist.org/packages/mailifica/mailifica-laravel)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

---

## ⚡ Features

- 🚀 **Native Laravel Mail Transport Driver**: Use `Mail::to()` or Mailables directly with Mailifica.
- 🔔 **Laravel Notification Channel**: Send rich notifications via `MailificaChannel` and `MailificaMessage`.
- 🪄 **Facade Support**: Full access to the REST API (`Mailifica::sendEmail()`, `Mailifica::listDomains()`, etc.).
- 🛡️ **Secure Webhooks**: Automatic HMAC-SHA256 signature verification & event dispatching (`MailificaWebhookReceived`).
- ⏱️ **Scheduled Sending & Metadata**: Native support for tags, metadata, scheduled delivery, and template rendering.
- 🧩 **Laravel 10.x, 11.x, 12.x, and 13.x Ready**: Works out of the box with modern PHP (>= 8.1).

---

## 📦 Installation

Install the package via Composer:

```bash
composer require mailifica/mailifica-laravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="mailifica-config"
```

---

## ⚙️ Configuration

### 1. Environment Variables (`.env`)

Add your Mailifica credentials to your `.env` file:

```env
MAIL_MAILER=mailifica
MAILIFICA_API_KEY=ma_live_your_api_key_here
MAILIFICA_BASE_URL=https://api.mailifica.com/v1

# Optional Webhook Secret for signature verification
MAILIFICA_WEBHOOK_SECRET=whsec_your_webhook_secret
```

### 2. Configure Mailer in `config/mail.php`

In your `config/mail.php`, register the `mailifica` mailer:

```php
'mailers' => [
    'mailifica' => [
        'transport' => 'mailifica',
    ],
    // ...
],
```

---

## 🚀 Usage

### 1. Sending Standard Mailables (`Mail::to()`)

Just use Laravel's standard `Mail` facade:

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

Mail::to('user@example.com')->send(new WelcomeEmail($user));
```

Or send raw emails:

```php
use Illuminate\Support\Facades\Mail;

Mail::raw('Hello from Mailifica!', function ($message) {
    $message->to('user@example.com')
            ->from('notifications@yourdomain.com', 'Acme App')
            ->subject('Welcome to Acme!')
            ->getHeaders()
            ->addTextHeader('X-Mailifica-Tag', 'category=welcome');
});
```

---

### 2. Sending via Laravel Notifications

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Mailifica\Laravel\Notifications\MailificaChannel;
use Mailifica\Laravel\Notifications\Messages\MailificaMessage;

class InvoicePaidNotification extends Notification
{
    public function via($notifiable): array
    {
        return [MailificaChannel::class];
    }

    public function toMailifica($notifiable): MailificaMessage
    {
        return MailificaMessage::create('Your Invoice Has Been Paid')
            ->from('billing@yourdomain.com')
            ->html('<h1>Thank you for your payment!</h1><p>Invoice #1023 is settled.</p>')
            ->tag('category', 'invoices')
            ->metadata('invoice_id', '1023')
            ->attachFromPath(storage_path('invoices/invoice-1023.pdf'));
    }
}
```

---

### 3. Using the Mailifica Facade

Directly call the REST API from anywhere in your application:

```php
use Mailifica\Laravel\Facades\Mailifica;

// Send transactional email
$response = Mailifica::sendEmail([
    'from' => 'Sender <hello@yourdomain.com>',
    'to' => 'receiver@example.com',
    'subject' => 'Verify your email address',
    'html' => '<strong>Click <a href="...">here</a> to verify.</strong>',
    'tags' => [
        ['name' => 'action', 'value' => 'verification'],
    ],
]);

// List verified domains
$domains = Mailifica::listDomains();

// Retrieve email delivery status
$email = Mailifica::getEmail('email_abc123');
```

---

### 4. Handling Inbound Webhooks

When a webhook arrives at the configured endpoint (default: `/mailifica/webhook`), `MailificaWebhookReceived` is automatically dispatched:

In `EventServiceProvider` (or `AppServiceProvider`):

```php
use Mailifica\Laravel\Events\MailificaWebhookReceived;
use Illuminate\Support\Facades\Event;

Event::listen(MailificaWebhookReceived::class, function (MailificaWebhookReceived $event) {
    switch ($event->type) {
        case 'email.delivered':
            // $event->data contains email details
            logger()->info('Email delivered: ' . $event->data['id']);
            break;

        case 'email.bounced':
            logger()->warning('Email bounced: ' . $event->data['recipient']);
            break;

        case 'email.complained':
            logger()->alert('Spam complaint received for: ' . $event->data['recipient']);
            break;
    }
});
```

---

## 🧪 Running Tests

```bash
vendor/bin/phpunit
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
