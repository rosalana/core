# Rosalana Core

Rosalana Core is the shared foundation for all applications in the Rosalana ecosystem. Its primary goal is to provide a unified framework of code, structures, and conventions that you can reuse across multiple **Laravel-based projects**.

> For more advanced features that are specific to certain functionalities, Rosalana provides additional packages.

> `rosalana/*` packages are meant to be used in Laravel applications with [Inertia](https://inertiajs.com/)

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Features](#features)
  - [CLI](#cli)
  - [Config Builder](#config-builder)
  - [Package Manager](#package-manager)
  - [Pipelines](#pipelines)
  - [Basecamp Connection](#basecamp-connection)
  - [Outpost Connection](#outpost-connection)
  - [App Context](#app-context)
  - [App Hooks](#app-hooks)
- [Ecosystem Versioning](#ecosystem-versioning)
- [May Show in the Future](#may-show-in-the-future)
- [License](#license)

## Installation

You can install `rosalana/core` via Composer by running the following command:

```bash
composer require rosalana/core
```

After installing the package, you should publish its assets using the following command:

```bash
php artisan rosalana:publish
```

You can specify which files to publish. Publishing **the configuration files is required** to set up the package properly. Other files are optional and can be published as needed. However, it is recommended to publish all files to take full advantage of the package features.

## Configuration

After publishing the package, you will find a `rosalana.php` configuration file in the `config` directory of your Laravel application. You can customize these options according to your needs.

This file will grow over time as you add more Rosalana packages to your application. Each package contributes its own configuration section. The `rosalana.php` file serves as the central configuration hub for all Rosalana packages.

`rosalana/core` package provides configuration options for:

- **basecamp**: Customize the connection to the central server which your application will connect to. More details [below](#basecamp-connection).
- **outpost**: Defines how your app connects to the **shared queue system**. Used for sending and receiving events across Rosalana applications.
- **published**: Tracks the packages that are currently installed and published in your application. This section is automatically updated when you publish a package. **Do not edit this section manually.**

## Features

### CLI

The Rosalana CLI is a developer tool that helps you manage and maintain packages within the Rosalana ecosystem. It allows you to install, update, remove, and configure packages through simple Artisan commands.

It’s designed to work with all current and future packages in the Rosalana ecosystem. Each package extends the CLI with its own logic and assets, enabling a smooth and unified development experience.

The CLI ensures that all Rosalana packages stay compatible with each other and with the core system. It follows the [ecosystem versioning standard](#ecosystem-versioning), so version management is handled automatically.

#### Available Commands

- `rosalana` – Show the current Rosalana ecosystem version in your project and list all available CLI commands.

- `rosalana:list` – Display all available and installed Rosalana packages based on the current ecosystem version.

- `rosalana:publish` – Publish package assets (such as config files, stubs, migrations). Use after installing a package or to update published files.

- `rosalana:add` – Add a new Rosalana package. CLI will automatically install the correct version based on the current ecosystem version.

- `rosalana:remove` – Remove a Rosalana package from your project and optionally clean up its configuration.

- `rosalana:update` – Update all installed packages to the latest available version for the current ecosystem. Or switch to a different ecosystem version entirely.

> Commands are blocked in production to prevent accidental modifications.

### Config Builder

Rosalana Core comes with a built-in **Config Builder** that allows you to programmatically manage the contents of the `rosalana.php` config file — **without overwriting existing user-defined values.**

This means you can safely publish or update configuration sections multiple times, and the builder will only insert missing keys without removing any of your changes.

The Config Builder also supports **PHP-style comments** (including title + description) that are rendered directly into the file. These help explain the purpose of each section clearly and keep config file human-readable even as it grows.

Every package in the Rosalana ecosystem uses the Config Builder to register its own configuration block. All sections are grouped inside a single rosalana.php file for easy navigation.

```php
use Rosalana\Core\Support\Config;

// Add or update a config section
Config::new('basecamp')
    ->add('secret',"env('ROSALANA_APP_SECRET', 'secret')")
    ->comment(
        'Connection settings for Basecamp (central server).',
        'Basecamp Connection'
    )
    ->save();

// Update a published version tag
Config::get('published')
    ->set('rosalana/xx', '1.0.0')
    ->save();
```

### Package Manager

The Rosalana ecosystem includes a **built-in package management system** that allows each package to describe itself, define what it publishes, and integrate with the CLI.

To make a package compatible with the Rosalana CLI (for use in commands like `rosalana:add`, `rosalana:update`, or `rosalana:publish`), it must register itself through a provider class that implements the `Rosalana\Core\Contracts\Package` interface.

#### Registering a Package

Each package must include a class in its `Providers` namespace with the same name as the package directory (e.g., `Core.php` for the `rosalana/core` package):

```php
namespace Rosalana\Core\Providers;

use Rosalana\Core\Contracts\Package;

class Core implements Package
{
    public function resolvePublished(): bool
    {
        // Self determining if the package is published
    }

    public function publish(): array
    {
        // Define what publish and how
        // (CLI will handle publish all automatically)
        return [
            'stuff' => [
                'label' => 'Publish some stuff',
                'run' => function () {
                    // Process publishing...
                }
            ],
        ];
    }
}
```

This is enough to make your package discoverable and manageable by the Rosalana CLI.

> **Tip:** You can define multiple publishing actions (e.g., `config`, `stubs`, `env`, `migrations`) in the `publish()` method to give the user flexibility.

#### Suporting the CLI

Rosalana keeps a hardcoded list of known packages (per ecosystem version) to **prevent incompatibility** and make installation reliable. Stored in `Rosalana\Core\Services\Package.php`.

> **Note:** If you don't see package in the CLI, it means that the package is not compatible with the current ecosystem version. Try `rosalana:update` to the same version or a newer one.

### Pipelines

The Rosalana Core includes a simple **pipeline system** that wraps around the Laravel pipeline. This allows packages to define and extend actions that should happen after certain events - like making a request to the Basecamp server.

Instead of handling logic inline, packages can define named pipelines and allow other packages to contribute additional logic to them **— without creating tight dependencies.**

> This makes cross-package coordination easy and flexible. Without the need to repeat the same actions in multiple packages.

Each pipeline is identified by a string alias. A package can register a pipeline and define what should happen when the pipeline is executed.

```php
use Rosalana\Core\Facades\Pipeline;

Pipeline::resolve('user.login')->extend(MyLoginHandler::class);
```

Other packages can extend the same pipeline without knowing if the original package is present or not.  
**Don't forget to return `$next($response)` to continue the pipeline execution.**  
If you omit the `return`, the pipeline will stop and the final result will be `null`.

```php
use Rosalana\Core\Facades\Pipeline;

Pipeline::extendIfExists('user.login', fn ($response, $next) => /* do something */);
```

Pipelines are executed automatically in some cases, like when a request is made to the Basecamp server. You can also trigger them manually.

```php
use Rosalana\Core\Facades\Pipeline;

Pipeline::resolve('user.login')->run($request);
```

> **Note:** The pipeline system can omit Laravel's built-in pipeline in the future.

### Basecamp Connection

> Connect to the central **Rosalana: Basecamp** server also used for synchronous communication between apps, while Outpost handles asynchronous event-based communication.

Every package built on `rosalana/core` can communicate with the central Basecamp server using a unified HTTP client provided by the `Rosalana\Core\Services\Basecamp\Manager::class`.

You can make requests to Basecamp in two different ways, depending on your use case

#### Direct Requests

The `Basecamp` facade gives you access to generic HTTP methods like `get()`, `post()`, `put()`, etc. You can use these methods to make requests to the Basecamp server directly.

```php
$response = Basecamp::get('/users/1');

$response = Basecamp::withAuth()
    ->withPipeline('user.login')
    ->post('/login', $credentials);
```

This approach is great for quick or dynamic requests without needing a dedicated service class.

#### Redirected Requests

By default, the `Basecamp` facade will send requests to the **central Basecamp server**. If you want to redirect the request to a different application in the Rosalana ecosystem, you can use the `to()` method.

```php
$response = Basecamp::to('app-name')
    ->withAuth()
    ->post('/projects', $payload);
```

The Basecamp client will automatically resolve the correct URL for the application and redirect the request to that app's API instead of the Basecamp server. All relevant headers — including authorization and pipeline identifiers — are forwarded automatically.

You can also combine the `to()` method with named services, though you must ensure the API structure is the same across all applications.

```php
$response = Basecamp::to('app-name')
    ->users()
    ->find(1);
```

> **Note:** This is useful for making cross-application requests without needing to know the exact URL of the target application.

#### Custom Services (Predefined API Actions)

For more structured and reusable logic, you can **define your own service** class and register it under a name. This adds a named accessor to the `Basecamp` facade, allowing you to call your service methods directly.

```php
use Rosalana\Core\Services\Basecamp\Service;

class UsersService extends Service
{
    public function find(int $id)
    {
        return $this->manager
            ->withAuth()
            ->get("users/{$id}");
    }

    public function all()
    {
        return $this->manager
            ->withAuth()
            ->withPipeline('user.login')
            ->get('users');
    }
}
```

Then, you need to register the service in the service provider.

```php
use Rosalana\Core\Services\Basecamp\Manager;

public function register()
{
    $this->app->resolving('rosalana.basecamp', function (Manager $manager) {
        $manager->registerService('users', new UsersService());
    });
}
```

After that, you can use the service in your application through the Basecamp facade.

```php
Basecamp::users()->get(1);
Basecamp::users()->login(['email' => 'a@a.com', 'password' => '...']);
```

You can chain `withAuth()` and `withPipeline()` methods on any request to handle authentication or trigger post-response pipelines.

All services registered this way automatically receive access to the underlying `Basecamp\Manager`, which manages headers, base URL, and request logic.

#### After Hook

You can attach custom logic to a Basecamp request by using `Basecamp::withPipeline('alias')`. This opens a hook window that other parts of your application can listen to.

To register a callback for a specific hook, use `Basecamp::after()` — typically inside your `AppServiceProvider`:

```php
use Illuminate\Http\Client\Response;
use Rosalana\Core\Facades\Basecamp;

public function register()
{
    Basecamp::after('user.login', function (Response $response) {
        // Handle post-login logic
    });
}
```

The callback will be executed after the Basecamp request is completed and if the request was explicitly marked with the `withPipeline()` method.

### Outpost Connection

> Send and receive **cross-application packets** asynchronously — as if they were local Laravel events. Outpost allows Rosalana applications to communicate over queues without losing simplicity.

The **Outpost system** lets you trigger events in other applications using Laravel queues. These packets are then converted back to Laravel events in the receiving application. That way, you can keep using standard `Event::listen(...)` and class-based listeners.

Under the hood, Outpost uses Laravel jobs for delivery and Laravel events for receiving — but your application doesn't need to know that.

#### Sending Packets

To send a packet, use the `Outpost::send()` method. You can optionally define which applications should receive the packet using `to()` or exclude some using `except()`.

```php
use Rosalana\Core\Facades\Outpost;

// Send to all apps (except yourself)
Outpost::send('user.registered', [
    'email' => 'john@example.com',
]);

// Send to a specific app
Outpost::to('app')->send('user.registered', [...]);

// Send to multiple apps
Outpost::to(['app1', 'app2'])->send('user.registered', [...]);

// Send to all except specific apps
Outpost::except('app')->send('user.registered', [...]);
```

#### Receiving Packets

To listen for incoming packets, use the `Outpost::receive()` method.
This automatically registers a Laravel event listener with the correct queue prefix (e.g. `outpost.user.registered`) based on your configuration.

```php
Outpost::receive('user.registered', App\Listeners\UserRegisteredListener::class);
```

You can also filter which apps you want to accept packets from using from() or exclude some using except():

```php
// Only receive from one app
Outpost::from('app')->receive('user.registered', ...);

// Receive from multiple apps
Outpost::from(['app1', 'app2'])->receive('user.registered', ...);

// Receive from all apps except one
Outpost::except('app')->receive('user.registered', ...);
```

#### Packet Payload

Each listener receives an instance of the original `Rosalana\Core\Services\Outpost\Packet` object, which includes:

```php
$packet->alias; // e.g. 'user.registered'
$packet->origin; // the app that sent the packet
$packet->target; // the app that should receive the packet
$packet->user(); // the basecamp user|null
$packet->payload; // data sent in the packet
```

You can access payload like this:

```php
public function handle(Packet $packet)
{
    $email = $packet->payload['email'];
    $localUser = Accounts::users()->toLocal($packet->user()); // return local user|null
}
```

> **Note:** You can use closures or class-based listeners, just like in Laravel. The Outpost::receive() wrapper ensures compatibility with your queue prefix.

### App Context

The App Context provides a centralized way to store and retrieve app-specific or user-specific data across the application lifecycle. It acts like a smarter cache and is especially useful for avoiding unnecessary Basecamp requests.

#### Accessing Context

Use `App::context()` to work with the local context.

```php
App::context()->get(); // Get full app context
App::context()->put('app', ['foo' => 'bar']);
```

You can also bind data to specific models or structured keys:

```php
App::context()->put($user, ['foo' => 'bar']);
App::context()->get($user); // ['foo' => 'bar']
App::context()->get('user.1.foo'); // 'bar'
App::context()->get([User::class, 1]); // ['foo' => 'bar']
App::context()->get([User::class, 1, 'foo']); // 'bar'
```

#### Forgetting Data

You can remove context data selectively:

```php
App::context()->invalidate('user.1.foo'); // Remove only one attribute
App::context()->invalidate('user.1'); // Remove entire user context
App::context()->flush(User::class); // Flush all entries for the User class (not implemented yet)
```

### App Hooks

Hooks let you register listeners that react to events across your application. Under the hood, this uses the Pipeline system.

#### Registering Hook

```php
App::hooks()->on('context:update', function ($data) {
    // Run custom logic after context is updated
});
```

You can also use camel-case helpers

```php
App::hooks()->onContextUpdate(function ($data) {
    // works like above
});
```

#### Triggering a Hook

```php
App::hooks()->run('context:update', [
    'key' => 'context.app',
    'path' => 'foo',
    'value' => 'bar',
    'previous' => null,
]);
```

## Ecosystem Versioning

Rosalana follows a unified versioning system. When you install or update packages, they are automatically matched to the correct version based on your current Rosalana ecosystem version.

```
X.Y.Z
│ │ └── Minor fix / patch in the package.
│ └──── Major change in the package but still compatible.
└────── Version of the Rosalana ecosystem
```

The [CLI](#cli) ensures package compatibility and prevents installing mismatched versions.

## May Show in the Future

- **Event system:** A system to allow packages create and listen to events across the ecosystem.
- **Plugin infrastructure**
- **Shared message-bus interfaces**
- **Realtime WebSocket integration**

Stay tuned — we're actively shaping the foundation of the Rosalana ecosystem.

## Bugs

- **Outpost**: when sending notification.email should be process ones but has no target app. Its target to basecamp. Možná hodit jen do lokální queue a zpracovat lokálně. Je fakt, že pokud něco není potřeba posílat do outpostu, tak to nebudeme posílat. Notification fasada by měla tohle rozhodovat. Jestli je potřeba posílat do outpostu nebo jestli jen poslat email nebo jen hodit do session.

## License

Rosalana Core is open-source under the [MIT license](/LICENCE), allowing you to freely use, modify, and distribute it with minimal restrictions.

You may not be able to use our systems but you can use our code to build your own.

For details on how to contribute or how the Rosalana ecosystem is maintained, please refer to each repository’s individual guidelines.

**Questions or feedback?**

Feel free to open an issue or contribute with a pull request. Happy coding with Rosalana!
