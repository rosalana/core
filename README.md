# Rosalana Core

Rosalana Core is the shared foundation for all applications in the Rosalana ecosystem. Its primary goal is to provide a unified framework of code, structures, and conventions that you can reuse across multiple Laravel-based projects. By installing `rosalana/core`, your application gains common event-driven features, consistent API patterns, and optional plugin capabilities—all without rewriting the same logic in each new project.

> **Note**: The Rosalana ecosystem also includes complementary packages like `rosalana/accounts`, `rosalana/notifications`, `rosalana/api`, and more. See the _Rosalana Architecture_ section below for an overview of how each package fits together.

---

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Local vs. Global Events](#local-vs-global-events)
- [Further Extensions](#further-extensions)
- [Rosalana Architecture Overview](#rosalana-architecture-overview)
- [Implementation Roadmap](#implementation-roadmap)
- [Additional Notes](#additional-notes)
    - [Packages](#packages)
    - [Event Handling](#event-handling)
    - [Notifications & Accounts](#notifications--accounts)
    - [WebSockets](#websockets)
- [License](#license)

---

## Features

### Centralized Core Logic

Define essential interfaces, abstract classes, and configuration for all Rosalana applications.

### Event-Driven Integration

Provide a standardized way to create and listen to events—both local (within a single app) and global (across multiple apps via a message broker like RabbitMQ, Redis Streams, or Kafka).

### Consistent Application Skeleton

Encourage each Laravel application to share similar structure (config files, queue settings, etc.) for easier development and maintenance.

### Extendable Plugin Mechanism

(Planned) Let each application install or publish specialized plugins that can exchange data and trigger cross-application logic.

### WebSockets

A unified approach to real-time communication (via Laravel Echo or similar), should you wish to enable front-end ↔ back-end updates within each app.

## Installation

1. Require the package via Composer:
    ```bash
    composer require rosalana/core
    ```

2. Publish (optional) the `rosalana.php` config file to adjust settings as needed:
    ```bash
    php artisan vendor:publish --provider="Rosalana\Core\Providers\CoreServiceProvider" --tag=rosalana-config
    ```

This will place a `config/rosalana.php` file in your Laravel application, where you can tailor the core behavior.

## Configuration

Inside `rosalana/core`, you’ll find a default configuration file in `config/rosalana.php`. For example:

```php
return [
    'events' => [
        // For local (per-application) event processing
        'local_connection'  => env('ROSALANA_LOCAL_CONNECTION', 'local-db'),
        'local_queue'       => env('ROSALANA_LOCAL_QUEUE', 'default'),

        // For global (cross-application) event processing
        'global_connection' => env('ROSALANA_GLOBAL_CONNECTION', 'global-rabbit'),
        'global_queue'      => env('ROSALANA_GLOBAL_QUEUE', 'global-events'),
    ],
    
    // Future expansions for plugin config, watchers, etc.
];
```

In your main Laravel app, define matching queue connections in `config/queue.php`. For instance:

```php
'connections' => [
    'local-db' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        // ...
    ],
    'global-rabbit' => [
        'driver' => 'rabbitmq', // or 'redis' if using Redis Streams
        'queue' => env('RABBIT_QUEUE', 'global-events'),
        // ...
    ],
    // ...
],
```

## Usage

After installing `rosalana/core`, your application can leverage core functionalities like abstract event classes, central config, and shared logic.

## Local vs. Global Events

The Rosalana ecosystem splits events into two categories:

### Local Events

- Implemented via an abstract class: `Rosalana\Core\Events\LocalEvent`.
- By default, dispatched to your local queue connection (e.g., database).
- Only processed within the originating app.

### Global Events

- Implemented via `Rosalana\Core\Events\GlobalEvent`.
- Typically sent to a global broker (e.g., RabbitMQ, Redis Streams).
- Can be listened to by other applications that share the same global queue.

### Example

```php
use Rosalana\Core\Events\LocalEvent;

class MyLocalThingHappened extends LocalEvent
{
    public $someData;

    public function __construct($someData)
    {
        parent::__construct(); 
        $this->someData = $someData;
    }
}

// Then you dispatch it:
event(new MyLocalThingHappened('example'));
```

Or for a global event:

```php
use Rosalana\Core\Events\GlobalEvent;

class UserRegisteredGlobally extends GlobalEvent
{
    public $userId;

    public function __construct($userId)
    {
        parent::__construct();
        $this->userId = $userId;
    }
}
```

When dispatched via `event(new UserRegisteredGlobally($id));`, it goes into the `global-rabbit` connection (or whichever is set in your config).

## Further Extensions

- **Plugin System:** A planned feature to let you define “plugins” that can be installed across various Rosalana apps for specialized functionality.
- **WebSockets:** The `rosalana/core` package aims to offer an easy approach to broadcasting real-time updates within each local app (not across apps).

## Rosalana Architecture Overview

The Rosalana ecosystem is built on a few key principles:

### Modularity & Code-Sharing

All core logic is abstracted out from specific domain logic into reusable packages (`rosalana/core`, `rosalana/accounts`, etc.).

### Unified User Accounts

A single identity provider (SSO) runs on a central “Basecamp” server (OAuth2). Each separate application can store additional user data locally.

### Event-Driven Integration

Applications communicate asynchronously via events, hooks, and a shared message-bus. REST APIs remain for synchronous calls or immediate responses.

### Common Tech Stack

Every final app is Laravel backend + Nuxt front-end, ensuring consistency and simplifying system-wide enhancements.

### Core sub-packages (beyond `rosalana/core`):

- `rosalana/accounts`: Manages OAuth2 client logic, user migrations, plus user-related events.
- `rosalana/notifications`: Common structure to send notifications (email, push, Slack, etc.).
- `rosalana/api`: Standardized REST API scaffolding and authentication solutions.
- `rosalana/cli`: A command-line tool to bootstrap new Rosalana apps.

Additionally, Rosalana: Basecamp is the main server that stores user accounts (SSO server) and acts as a reference for configuration.

## Implementation Roadmap

Below is a simplified roadmap (subject to change) when adopting the full Rosalana approach:

1. Build `rosalana/core` (this package) with basic skeleton.
2. Create OAuth2 Server (Rosalana: Basecamp) using Laravel Passport.
3. Develop `rosalana/accounts` for client integration.
4. Set up a consumer app (e.g., Rosalana Support) to test SSO.
5. Refine events in `rosalana/core`; define or connect to `rosalana/notifications`.
6. Implement REST API standards in `rosalana/api`.
7. Add plugin structure in `rosalana/core`.
8. Spin up a “dummy app” to test inter-app communication.
9. Polish notifications and other features.
10. Create `rosalana/cli` for quickly scaffolding new apps.

## Additional Notes

### Packages

Each sub-package is typically its own repository (and published as a separate Composer / NPM package). For instance, you might have:

- `rosalana/core-laravel` and `rosalana/core-nuxt` for back-end vs. front-end logic.
- Clear, separate `README.md` files that explain each package’s purpose.

### Event Handling

Rosalana’s event-based approach can use:

- Shared broker: e.g., RabbitMQ, Redis Streams, or Kafka, for truly asynchronous cross-application events.
- Local queues: Each application can handle internal events or direct triggers without involving external brokers.

### Notifications & Accounts

- `rosalana/notifications`: Not a separate app but a module offering APIs like `Notifications::send(...)` and handling cross-app event-driven alerts or emails.
- `rosalana/accounts`: Ties each app into the central OAuth2 server (“Basecamp”), providing a standard user model, migrations, and login flows.

### WebSockets

Real-time communication typically stays local to each app’s front-end and back-end (via `laravel-websockets`, Pusher, or similar). If an app receives a global event from the broker, it can decide whether to broadcast it to its own users.

## License

Rosalana Core is open-source under the [MIT license](/LICENCE), allowing you to freely use, modify, and distribute it with minimal restrictions.

For details on how to contribute or how the Rosalana ecosystem is maintained, please refer to each repository’s individual guidelines.

**Questions or feedback?**

Feel free to open an issue or contribute with a pull request. Happy coding with Rosalana!