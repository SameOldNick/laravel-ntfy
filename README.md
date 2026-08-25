# Laravel Ntfy

[![Packagist](https://img.shields.io/packagist/v/sameoldnick/laravel-ntfy)](https://packagist.org/packages/sameoldnick/laravel-ntfy)
[![codecov](https://codecov.io/gh/SameOldNick/laravel-ntfy/graph/badge.svg?token=FnWsyYNQZZ)](https://codecov.io/gh/SameOldNick/laravel-ntfy)

A Laravel package for sending [ntfy](https://ntfy.sh/) push notifications. It provides a native Laravel notification channel and a standalone service, built on the [Ntfy PHP Library](https://github.com/VerifiedJoseph/ntfy-php-library).

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Sending notifications](#sending-notifications)
- [Sending messages directly with the service](#sending-messages-directly-with-the-service)
- [Testing](#testing)
- [Handling responses](#handling-responses)

## Requirements

- PHP 8.4+
- Laravel 11 / 12 / 13

## Installation

Install the package via Composer:

```bash
composer require sameoldnick/laravel-ntfy
```

The package's service provider and `Ntfy` facade are registered automatically via package discovery — no manual registration is required.

Publish the configuration file:

```bash
php artisan vendor:publish --tag=ntfy-config
```

### Optional: per-user configuration model

If you want to store each user's ntfy destination (server, topic, credentials) in the database, publish and run the migration that creates the `ntfy_configurations` table:

```bash
php artisan vendor:publish --tag=ntfy-migrations
php artisan migrate
```

You can skip this step if you only send messages to hard-coded or config-driven destinations using the standalone service (see below).

## Configuration

The published `config/ntfy.php` file exposes the following environment variables:

| Environment variable           | Default            | Description                                                                                                           |
| ------------------------------ | ------------------ | --------------------------------------------------------------------------------------------------------------------- |
| `NTFY_ENABLED`                 | `false`            | Enables/disables the notification **channel**. The standalone service is always available regardless of this setting. |
| `NTFY_SERVER_URL`              | `https://ntfy.sh/` | The ntfy server URL.                                                                                                  |
| `NTFY_DEFAULT_TOPIC`           | `null`             | A default topic used when none is specified.                                                                          |
| `NTFY_AUTH_USERNAME`           | `""`               | Username for basic auth (`user` method).                                                                              |
| `NTFY_AUTH_PASSWORD`           | `""`               | Password for basic auth (`user` method).                                                                              |
| `NTFY_AUTH_TOKEN`              | `""`               | Access token for token auth (`token` method).                                                                         |
| `NTFY_HTTP_TIMEOUT`            | `10`               | Request timeout in seconds.                                                                                           |
| `NTFY_HTTP_CONNECT_TIMEOUT`    | `10`               | Connection timeout in seconds.                                                                                        |
| `NTFY_HTTP_VERIFY_SSL`         | `true`             | Whether to verify SSL certificates.                                                                                   |
| `NTFY_HTTP_RETRY_ENABLED`      | `true`             | Whether failed requests are retried.                                                                                  |
| `NTFY_HTTP_RETRY_MAX_ATTEMPTS` | `3`                | Number of retry attempts.                                                                                             |
| `NTFY_HTTP_RETRY_DELAY`        | `100`              | Delay between retries in milliseconds.                                                                                |

> **Note:** `ntfy.enabled` only controls the notification channel. When it is `false`, notifications routed through `via('ntfy')` are skipped (with a log entry), but you can still send messages directly through the `Ntfy` service/facade.

## Sending notifications

This is the standard Laravel approach: route a `Notification` to a notifiable (e.g. a `User`). There are two parts — telling Laravel _where_ to send the message, and telling it _what_ to send.

### 1. Set the destination

There are two ways to define where ntfy messages for a notifiable should be delivered.

**Option A — the `NtfyConfiguration` model (per-user, stored in the database).**

Add the `NtfyNotifiable` trait to your notifiable model. This adds a `ntfyConfiguration()` morph-one relationship and resolves the destination from the `ntfy_configurations` table automatically.

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use SameOldNick\Ntfy\Concerns\NtfyNotifiable;

class User extends Authenticatable
{
    use NtfyNotifiable;
}
```

Then store the user's destination, for example:

```php
$user->ntfyConfiguration()->create([
    'server_url' => 'https://ntfy.example.com',
    'topic' => 'alerts',
    'auth_token' => 'tk_...', // or 'username' + 'password'
]);
```

**Option B — manual routing (hard-coded destination).**

Define a `routeNotificationForNtfy()` method on the notifiable, returning an array (or a `ServerInfo` instance — see below):

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public function routeNotificationForNtfy($notification = null)
    {
        return [
            'server_url' => 'https://ntfy.example.com',
            'topic' => 'alerts',

            // Use one of these for authentication (optional):
            // 'auth_username' => 'user',
            // 'auth_password' => 'password',
            // 'auth_token' => 'tk_...',

            // Per-user HTTP options (overrides the 'ntfy.http' config):
            // 'options' => [],
        ];
    }
}
```

### 2. Build the message

Create a notification that sends via the `ntfy` channel and defines a `toNtfy()` method:

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Ntfy\Message;
use SameOldNick\Ntfy\Services\MessageBuilder;

class MyNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['ntfy'];
    }

    public function toNtfy(object $notifiable): Message
    {
        return MessageBuilder::make()
            ->title('This is the title')
            ->body('This is the message')
            ->priority(3)
            ->tags(['green', 'red'])
            ->build();
    }
}
```

> **Note:** The topic set on the destination (`routeNotificationForNtfy` / `NtfyConfiguration`) always takes precedence over any topic set on the message.

### 3. Send it

```php
use App\Notifications\MyNotification;

$user->notify(new MyNotification);
```

## Sending messages directly with the service

When you don't have a notifiable (for example, a CLI command, scheduled job, or health check), send a message directly through the `Ntfy` facade:

```php
use Ntfy\Exception\EndpointException;
use Ntfy\Exception\NtfyException;
use SameOldNick\Ntfy\DTOs\ServerInfo;
use SameOldNick\Ntfy\Facades\Ntfy;
use SameOldNick\Ntfy\Services\MessageBuilder;

$message = MessageBuilder::make()
    ->title('Server down')
    ->body('The web server is unreachable.')
    ->priority(5)
    ->build();

$server = ServerInfo::createWithToken(
    url: 'https://ntfy.example.com',
    topic: 'alerts',
    token: 'tk_...',
);

try {
    $response = Ntfy::send($message, $server);

    // Message sent successfully.
    $response->id(); // unique message ID
} catch (NtfyException|EndpointException $ex) {
    // Handle the error.
}
```

### `ServerInfo` factory methods

`ServerInfo` describes the destination. Use one of these helpers:

```php
// Read the destination from the 'ntfy.global' config block.
$server = ServerInfo::fromConfig();

// Build from an array (e.g. the output of routeNotificationForNtfy).
$server = ServerInfo::fromArray([
    'server_url' => 'https://ntfy.example.com',
    'topic' => 'alerts',
    'auth_token' => 'tk_...',
]);

// Username/password authentication.
$server = ServerInfo::createWithAuth(
    url: 'https://ntfy.example.com',
    username: 'user',
    password: 'password',
    topic: 'alerts',
);

// Token authentication.
$server = ServerInfo::createWithToken(
    url: 'https://ntfy.example.com',
    token: 'tk_...',
    topic: 'alerts',
);

// No authentication.
$server = ServerInfo::createWithoutAuth(
    url: 'https://ntfy.example.com',
    topic: 'alerts',
);
```

### `MessageBuilder` methods

`MessageBuilder` is a fluent builder that wraps the underlying `Ntfy\Message`. Commonly used methods:

```php
$message = MessageBuilder::make()
    ->topic('alerts')        // usually overridden by the destination topic
    ->title('Title')
    ->body('Plain-text body')
    ->markdown('# Markdown body')
    ->priority(3)            // 1 (low) - 5 (high)
    ->tags(['warning', '🚨'])
    ->click('https://example.com')   // URL opened when the notification is clicked
    ->icon('https://example.com/icon.png')
    ->schedule('10m')        // delay delivery, e.g. "30s", "1h", "1d"
    ->attach('https://example.com/file.pdf', 'report.pdf')
    ->email('ops@example.com')
    ->build();
```

Any other method on `Ntfy\Message` can be called directly thanks to method forwarding.

## Testing

Use the `Ntfy` facade's `fake()` helper to prevent real HTTP requests and assert on messages in your tests:

```php
use SameOldNick\Ntfy\Facades\Ntfy;

Ntfy::fake();

// ... run code that sends notifications ...

Ntfy::assertSent(fn ($message) => $message->getData()['title'] === 'Server down');
Ntfy::assertSentCount(1);
Ntfy::assertNothingSent();
```

## Handling responses

A successful send returns a `MessageResponse` object with these accessors:

```php
$response = Ntfy::send($message, $server);

$response->id();        // unique message ID
$response->time();      // unix timestamp
$response->dateTime();  // DateTimeImmutable / Carbon instance
$response->topic();     // topic the message was sent to
$response->message();   // body content
$response->title();     // title
$response->priority();  // priority (1-5)
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
