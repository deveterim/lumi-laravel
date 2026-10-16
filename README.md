# Lumi Laravel Driver & SDK

> Official Laravel integration for the **Lumi** Transactional & Marketing Email Infrastructure.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lumi/lumi-laravel.svg?style=flat-square)](https://packagist.org/packages/lumi/lumi-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/lumi/lumi-laravel.svg?style=flat-square)](https://packagist.org/packages/lumi/lumi-laravel)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

---

## ⚡ Features

- 🚀 **Native Laravel Mail Transport Driver**: Use `Mail::to()` or Mailables directly with Lumi.
- 🔔 **Laravel Notification Channel**: Send rich notifications via `LumiChannel` and `LumiMessage`.
- 🪄 **Facade Support**: Full access to the REST API (`Lumi::sendEmail()`, `Lumi::listDomains()`, etc.).
- 🛡️ **Secure Webhooks**: Automatic HMAC-SHA256 signature verification & event dispatching (`LumiWebhookReceived`).
- ⏱️ **Scheduled Sending & Metadata**: Native support for tags, metadata, scheduled delivery, and template rendering.
- 🧩 **Laravel 10.x, 11.x, 12.x, and 13.x Ready**: Works out of the box with modern PHP (>= 8.1).

---

## 📦 Installation

Install the package via Composer:

```bash
composer require lumi/lumi-laravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="lumi-config"
```

---

## ⚙️ Configuration

### 1. Environment Variables (`.env`)

Add your Lumi credentials to your `.env` file:

```env
MAIL_MAILER=lumi
LUMI_API_KEY=ma_live_your_api_key_here
LUMI_BASE_URL=https://api.lumi.email/v1

# Optional Webhook Secret for signature verification
LUMI_WEBHOOK_SECRET=whsec_your_webhook_secret
```

### 2. Configure Mailer in `config/mail.php`

In your `config/mail.php`, register the `lumi` mailer:

```php
'mailers' => [
    'lumi' => [
        'transport' => 'lumi',
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

Mail::raw('Hello from Lumi!', function ($message) {
    $message->to('user@example.com')
            ->from('notifications@yourdomain.com', 'Acme App')
            ->subject('Welcome to Acme!')
            ->getHeaders()
            ->addTextHeader('X-Lumi-Tag', 'category=welcome');
});
```

---

### 2. Sending via Laravel Notifications

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Lumi\Laravel\Notifications\LumiChannel;
use Lumi\Laravel\Notifications\Messages\LumiMessage;

class InvoicePaidNotification extends Notification
{
    public function via($notifiable): array
    {
        return [LumiChannel::class];
    }

    public function toLumi($notifiable): LumiMessage
    {
        return LumiMessage::create('Your Invoice Has Been Paid')
            ->from('billing@yourdomain.com')
            ->html('<h1>Thank you for your payment!</h1><p>Invoice #1023 is settled.</p>')
            ->tag('category', 'invoices')
            ->metadata('invoice_id', '1023')
            ->attachFromPath(storage_path('invoices/invoice-1023.pdf'));
    }
}
```

---

### 3. Using the Lumi Facade

Directly call the REST API from anywhere in your application:

```php
use Lumi\Laravel\Facades\Lumi;

// Send transactional email
$response = Lumi::sendEmail([
    'from' => 'Sender <hello@yourdomain.com>',
    'to' => 'receiver@example.com',
    'subject' => 'Verify your email address',
    'html' => '<strong>Click <a href="...">here</a> to verify.</strong>',
    'tags' => [
        ['name' => 'action', 'value' => 'verification'],
    ],
]);

// List verified domains
$domains = Lumi::listDomains();

// Retrieve email delivery status
$email = Lumi::getEmail('email_abc123');
```

---

### 4. Handling Inbound Webhooks

When a webhook arrives at the configured endpoint (default: `/lumi/webhook`), `LumiWebhookReceived` is automatically dispatched:

In `EventServiceProvider` (or `AppServiceProvider`):

```php
use Lumi\Laravel\Events\LumiWebhookReceived;
use Illuminate\Support\Facades\Event;

Event::listen(LumiWebhookReceived::class, function (LumiWebhookReceived $event) {
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
