# Rosalana Core

Rosalana Core is the shared foundation for all applications in the Rosalana ecosystem. Its primary goal is to provide a unified framework of code, structures, and conventions that you can reuse across multiple **Laravel-based projects**.

> For more advanced features that are specific to certain functionalities, Rosalana provides additional packages.

> `rosalana/*` packages are meant to be used in Laravel applications with [Inertia](https://inertiajs.com/)

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Features](#features)
  - [CLI](#cli)
  - [Package Manager](#package-manager)
  - [Internal API](#internal-api)
  - [Pipelines](#pipelines)
  - [Trace System](#trace-system)
  - [Basecamp Connection](#basecamp-connection)
  - [Outpost Connection](#outpost-connection)
  - [App Context](#app-context)
  - [App Hooks](#app-hooks)
- [Available Hooks](#available-hooks)
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
- **revizor**: Settings for the authorization engine that allows cross-application communication.
- **tracer**: Configuration for the built-in runtime tracing system. Core package provides the `runtime` section. When `rosalana/tracer` package is installed, additional options will appear.
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

> **Tip:** You can define multiple publishing actions (e.g., `config`, `stubs`, `env`, `migrations`, `routes`) in the `publish()` method to give the user flexibility.

#### Suporting the CLI

Rosalana keeps a hardcoded list of known packages (per ecosystem version) to **prevent incompatibility** and make installation reliable. Stored in `Rosalana\Core\Services\Package.php`.

> **Note:** If you don't see package in the CLI, it means that the package is not compatible with the current ecosystem version. Try `rosalana:update` to the same version or a newer one.

### Internal API

> Automatic JSON API system for internal routes with middleware and exception handling.

Rosalana Core automatically sets up `/internal` routes protected by middleware and with automatic exception handling. This is used for **App2App communication** when applications call each other directly.

#### API Overview

| Endpoint | Method | Middleware       | Description           |
| -------- | ------ | ---------------- | --------------------- |
| `/ping`  | GET    | `revizor.ticket` | Just for health check |

#### Authentication Flow

All internal routes are protected by the `RevizorCheckTicket` middleware. This middleware checks for a valid stringified **ticket** in the `Authorization bearer {ticket}` header. The ticket generated by the **Basecamp server** when making requests to other applications.

#### Success Response

```json
{
  "status": "ok",
  "data": { "id": 1, "name": "John Doe" },
  "meta": {
    "token": "...",
    "expires_at": "2023-10-01T12:00:00Z"
  }
}
```

#### Error Response

```json
{
  "status": "error",
  "message": "Validation failed.",
  "code": 422,
  "type": "VALIDATION",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

> All responses return HTTP 200 status. Actual success/failure is determined by `status` field.

For convenience, responses are returned using `ok()` and `error()` helpers with chainable methods (e.g. `error()->unauthorized()`).

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

### Trace System

Rosalana Core includes a **lightweight runtime tracing system** designed to observe and analyze how application logic flows during execution.

The Trace system is focused on **runtime behavior** but can be also used for performance monitoring and debugging. It is safe to use in production and introduces minimal overhead.

Tracing can be disabled globally via configuration if needed.

#### Creating a Trace

A trace represents a **top-level operation**, such as handling a request or running a worker task.

To start a trace, you use the `Trace::start()` method, providing a name for the operation. This initializes a new trace context.

When the operation finishes, call `Trace::finish()` to close the trace and retrieve the final trace object.

```php
use Rosalana\Core\Facades\Trace;

Trace::start('operation.name');

// Your operation logic here

$trace = Trace::finish();
```

> [!TIP]
> The returned `Trace` object contains the full execution tree and can be inspected or filtered.

#### Phases (Sub-operations)

Inside a trace, you can create phases to represent meaningful sub-operations or steps in the execution flow.

A phase is started using `Trace::phase()` and returns a `Scope` object.

> [!IMPORTANT]
> Always store the returned `Scope` instance in a variable.
> If it is not stored, the phase will be closed immediately.

```php
$phase = Trace::phase('sub-operation');

// Your sub-operation logic here

$phase->close(); // Optional manual close
```

Phases are automatically closed when their scope is destroyed (for example when leaving the current block or when an exception occurs).

#### Records, Exeptions and Decisions

During execution, you can attach **records** to the current active trace or phase.

##### Records

```php
Trace::record(mixed $data = null);
Trace::recordWhen(bool $condition, mixed $data = null);
```

Records are simple data points that represent informational runtime events.

##### Exceptions / Failures

```php
Trace::fail(\Throwable $error, mixed $data = null);
Trace::exception(\Throwable $error, mixed $data = null);
```

Failure records mark an execution path as failed and are used later for trace filtering.

During each phase or the main trace, you can add custom records to log specific events or data points.

##### Decisions

Decisions are special records that mark **which execution path was chosen**.

```php
Trace::decision(mixed $data = null);
Trace::decisionWhen(bool $condition, mixed $data = null);
Trace::recordOrDecision(bool $isDecision, mixed $data = null);
```

A phase can contain **only one** decision record. But a trace can have multiple decisions across its phases. Decisions are used to extract the **actual execution path** from complex branching logic.

#### Automatic Trace Capture

For convenience, you can wrap your operations in a single method that handles trace creation and completion automatically.

This is useful for quickly adding tracing to existing code without modifying its structure significantly. It also ensures that traces are always properly finished, even if exceptions occur. When an exception is thrown inside the callback, it is caught and recorded as a failure record before rethrowing it.

```php
use Rosalana\Core\Facades\Trace;

Trace::capture(function () {
    // Your operation logic here
}, 'operation.name');
```

#### Working with Trace Objects

The returned Trace object is not just data — it provides powerful helpers to analyze execution flow:

- `hasException()` – Check if the trace contains any exception records.
- `hasDecision()` – Check if the trace contains any decision records.
- `onlyDecisionPath()` – Extract a new trace containing only the phases and records that led to decisions.
- `onlyFailedPath()` – Extract a new trace containing only the phases and records that led to failures.
- `onlyDominantPath()` - Extract a new trace containing only the dominant execution path (time wise).

You can still export a trace into a serializable structure if needed:

```php
$traceArray = $trace->toArray();
```

```json
{
    "id": "...",
    "name": "operation-name",
    "duration": 13.03,
    "records": [],
    "phases": [
        {
            "id": "...",
            "name": "sub-operation",
            "duration": 5.67,
            "records": [
                {
                    "type": "record|exception|decision",
                    "timestamp": 1696543210.1234,
                    "exception": {...}, // if type is exception
                    "data": {...}
                }
            ],
            "phases": []
        }
    ]
}
```

#### Logging Traces

You can log the final trace (or any sub-trace) using the built-in log targets.

```php
Trace::finish()->log('console');
```

By default, Rosalana Core provides `console` and `file` log targets.

You can put custom target class and place it in the `log` function parameter.

```php
Trace::finish()->log(MyCustomTarget::class);
```

Targets are **abstract classes** that define **where to send the rendered logs**. Your custom target must extend the `Rosalana\Core\Services\Trace\Rendering\Target` class and implement the `publish(array $lines): void` method.

Logs are created per-trace and per-target. The render options are defined in **final class** extending the coresponding target. You can imagine renderers as implementation of the target logic.

If you want to send logs to `console`, you can extend the `Rosalana\Core\Trace\Target\Console` class and implement the `render(Trace $trace)` method. You build the log token by token using the `token(string $text)` method. Each target provides helpers to build the log.

> [!NOTE]
> Exceptions are rendered automatically unless you override the `renderException()` method.

```php
final class OperationConsole extends Console
{
    public function render(Trace $trace): void
    {
        $this->time($trace->startTime());
        $this->space(); // add space
        $this->token(" --- Operation Trace Log --- ", 'red'); // red token

        $this->newLine(); // move to new line

        $this->token("Operation: {$trace->name()}");
        $this->space();
        $this->dot(5); // add 5 dots
        $this->space();
        $this->arrow('right'); // add arrow pointing right
        $this->space();
        $this->duration($trace->duration());

              -------- ↓ RESULT ↓ ---------

        [12:34:56.789] --- Operation Trace Log ---
        Operation: operation.name ..... → 13.03ms

    }
}
```

For each target you need to register the implementation for each trace name or pattern. When logging, the traces will resolve the correct target automatically. When no match is found, trace will be skipped.

```php
Trace::register([
    'operation.*' => [
        OperationConsole::class,
        OperationFile::class,
        OperationCustom::class,
    ],
    'operation.{create|update}' => [OperationDetailConsole::class],
]);
```

The more specific wildcard match takes precedence. So `operation.create` will match `operation.{create|update}` scheme before `operation.*`.

Your custom **abstract target** can also be registered globally if used often.

```php
Trace::targetAlias('custom', Custom::class);
```

Then you can use the alias when logging.

```php
Trace::finish()->log('custom');
```

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

#### Advanced Request Options

You can customize Basecamp requests further by chaining additional methods on the `Basecamp` facade.

```php
Basecamp::timeout(10); // Set custom timeout (seconds)
Basecamp::retry(3); // Retry failed requests (times)
Basecamp::ghost(); // Skip pipeline execution
Basecamp::version('v2'); // Use specific API version

Basecamp::mock(); // Mock the request (for testing)

Basecamp::fallback(function () {
    // Custom fallback logic when request fails
});
```

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

> [!NOTE]
> Send and receive **cross-application messages** asynchronously. Outpost allows Rosalana applications to communicate over queues without losing simplicity.

The **Outpost system** lets you trigger events in other applications. It uses **Redis Streams** as the underlying message bus, allowing applications to send and receive messages asynchronously. It uses Rosalana's action system which acts like Laravel event and listener at one.

#### Outpost Setup

At this moment, [rosalana/configure](https://github.com/rosalana/configure) package can not modify `config/database.php` to add Redis connection automatically. You need to add it manually. It is required to use connection without prefix.

#### Message Convention

Outpost messages has a specific structure to ensure compatibility across applications. Each is identified by an namespace alias containing tree parts:

```
{group}.{action}:{status}
```

- **group**: The main category of the message (e.g., `user`, `notification`, `order`).
- **action**: The specific event or action being communicated (e.g., `created`),
- **status**: represents the state of the message (`request`, `confirmed`, `failed`, `unreachable`).

Namespaces are created automatically by the `Outpost` facade when sending. Just provide the `group.action` part and the status is appended based on the method used.

#### Sending Messages

To send messages between applications, you have to always specify the receiving application(s).

```php
use Rosalana\Core\Facades\Outpost;

Outpost::to('app-slug');
Outpost::to(['app1', 'app2']);
Outpost::broadcast(); // to all apps except yourself
Outpost::broadcast()->except('app-slug'); // to all except specific app
```

When sending, you can choose to send to a specific app, multiple apps, or broadcast to all apps (except yourself). After defining the target(s), you can send the message.

```php
Outpost::to('app-slug')->request('group.action', [...]);
Outpost::to('app-slug')->confirm('group.action', [...]);
Outpost::to('app-slug')->fail('group.action', [...]);
Outpost::to('app-slug')->unreachable('group.action', [...]);
```

In a day-to-day usage, you will mostly use the `request()` method to send messages. The other methods are used to respond to incoming messages.

#### Receiving Messages

##### Handling Promises

When you send a message using any of the sending methods (`request()`, `confirm()`, `fail()`, `unreachable()`), you can handle the response using **promises**.

Each of these methods returns an instance of `Rosalana\Core\Services\Outpost\Promise`, which you can use to track the status of the message.

Promise lets you define callbacks for when your message is confirmed, failed, or unreachable.

```php
use Rosalana\Core\Services\Outpost\Message;

$promise = Outpost::to('app-slug')->request('group.action', [...]);

$promise->onConfirm(function (Message $message) {
    // Handle confirmation
});

$promise->onFail(function (Message $message) {
    // Handle failure
});

$promise->onUnreachable(function (Message $message) {
    // Handle unreachable
});
```

You can manually **reject** the promise if needed:

```php
$promise->reject();
```

This will clear all the stored promises for the message and prevent any further callbacks from being executed.

Resolving promises is handled automatically by the Outpost worker when responses are received. When a promise is resolved, the corresponding callback is executed with the received `Message` instance.

After resolving, all unused callbacks are cleared and the promise is considered complete.

##### Class-based Listeners

Class-based listeners are a specific way to handle incoming Outpost messages. In configuration, you can define a namespace where your listeners are stored. Outpost will automatically resolve the correct listener class based on the message namespace alias.

For example, if you have a message with the alias `project.link`, Outpost will look for a `\App\Outpost\Project\Link.php` listener class.

There is always one listener per message, which handles all incoming statuses (`request`, `confirmed`, `failed`, `unreachable`).

This class must extend the `Rosalana\Core\Services\Outpost\Listener` class and implement the `request()` method to handle incoming requests.

Other methods (`confirmed()`, `failed()`, `unreachable()`) are optional and can be implemented if you want to handle those statuses specifically.

Each method receives an instance of `Rosalana\Core\Services\Outpost\Mesage`, which contains all relevant information about the incoming message.

You can return an instance of Laravel's `Event` or just run custom logic directly in the method.

You can also return an instance of `Rosalana\Core\Services\Actions\Action` to create a event-listener like behavior in one go.

```php

public function request(Message $message)
{
    return $message->event(function (Message $message) {
        // Handle the event logic here
    });
}
```

You may ask. How is this different from just writing the logic directly in the `request()` method?

The `event()` method wraps your logic in an Action, allowing you to leverage the action system's features, such as queuing and broadcasting. This means that your event can be processed asynchronously or broadcasted via WebSockets if needed.

And all of this just by simple function.

```php
return $message->event(fn (Message $message) => ...)
    ->queue()
    ->broadcast();
```

The broadcasting configuration is handled automatically from the receiving message. But you can override it if needed. Look at the example after receiving message with namespace `project.link:confirmed`.

```php
$event->broadcast();
// channel: 'project-link'
// event: 'project.link.confirmed'

$event->broadcast('custom-channel', 'custom-event');
```

> [!NOTE]
> Rosalana Actions system will be extended in the future to support more features like delayed execution, retries, and more.

The `Message` class also provides helper methods to help you in your logic.

```php
public function request(Message $message)
{
    // You can quickly respond to the sender
    $message->confirm([...]);
    $message->fail([...]);
    $message->unreachable([...]);

    // Check if the message is from a specific app
    $message->isFrom('app-slug');
    $message->payload('key', 'default');

    // Get the promise of the message (for advanced usage)
    // you can override the promises or reject them
    $message->promise();
}
```

> [!TIP]
> If you throw an exception inside any of the listener methods, Outpost will automatically send a `failed` response back to the sender.

##### Registering Listeners

> [!NOTE]
> Listen for incoming messages using the `Outpost::receive()` method. Is for advanced usage, when you want to register listeners dynamically.

You can register listeners dynamically using the `Outpost::receive()` method. This is useful when you want to handle messages without creating dedicated listener classes.

You can also register silent listeners that do not interfere with class-based listeners. The action from silent listener will not be considered as handler of the message.

Registering is typically done in the `AppServiceProvider` or a dedicated service provider. You need to provide the full namespace alias (including status) and a callback function that will handle the incoming message.

```php
public function register()
{
    Outpost::receive('group.action:status', function (Message $message) {
        // Handle incoming request
    });

    Outpost::receiveSilently('group.action:status', function (Message $message) {
        // Handle incoming request silently
    });
}
```

You are able to use Rosalana Actions inside the callback as well. Just return an action instance.

```php
Outpost::receive('group.action:status', function (Message $message) {
    return $message->event(function (Message $message) {
        // Handle the event logic here
    })->broadcast();
});
```

> [!TIP]
> You can use `wildcards` in the namespace alias when registering listeners. This allows you to create more generic handlers that can respond to multiple message types.

#### Custom Services (Predefined API Actions)

For more structured and reusable logic, you can **define your own service** class and register it under a name. This adds a named accessor to the `Outpost` facade, allowing you to call your service methods directly.

```php
use Rosalana\Core\Services\Outpost\Service;
use Rosalana\Core\Services\Outpost\Message;

class ProjectService extends Service
{
    public function link(string $target, array $payload)
    {
        return $this->manager
            ->to($target)
            ->request('project.link', $payload)
            ->onConfirm(function (Message $message) {
                // Handle confirmation
            });
    }
}
```

Then, you need to register the service in the service provider.

```php
use Rosalana\Core\Services\Outpost\Manager;

public function register()
{
    $this->app->resolving('rosalana.outpost', function (Manager $manager) {
        $manager->registerService('project', new ProjectService());
    });
}
```

After that, you can use the service in your application through the Basecamp facade.

```php
Outpost::project()->link('app-slug', [...]);
```

All services registered this way automatically receive access to the underlying `Outpost\Manager`, which manages headers, base URL, and request logic.

### App Context

> [!IMPORTANT]
> Context storage requires a PHP-Redis connection to work.

The App Context provides a centralized way to store and retrieve app-specific or user-specific data across the application lifecycle. It acts like a smarter cache and is especially useful for avoiding unnecessary Basecamp requests.

It uses Redis as the underlying storage mechanism, ensuring fast access and scalability. It supports structured keys, allowing you to bind data to specific models or entities. Every key count be set with a TTL (time to live) to automatically expire data after a certain period.

#### Accessing Context

App Context is accessible via the `App::context()` facade. The whole context is segmented into scopes, with the default scope being `__app`. Default scope is meant for storing application-wide data. For user-specific data, you can use the user scope `user.{id}`.

Scopes can be changed by using the `scope()` method. For accesing app-wide context, you don't need to change the scope.

```php
App::context(); // scope: __app
App::context()->scope('user.1'); // scope: user.1
App::context()->scope($user); // scope: user.{id}
App::context()->scope([User::class, 1]); // scope: user.1
```

Once you have set the desired scope, you can work with the context data within that scope.

```php
$scope->get('foo', 'default'); // Get value with default
$scope->put('nested.foo', 'bar'); // Set value
$scope->has('foo'); // Check if key exists
$scope->receive(); // Get all data in the scope
```

Value can be mixed types, including arrays and objects. Nested keys are supported using dot notation.

It's possible to dump the whole context. Don't set any scope for this.

```php
App::context()->all(); // Get full app context
App::context()->raw(); // Get raw Redis data
```

From the global view you can also find data using patterns:

```php
App::context()->find('user.*', ['role' => 'admin']);
```

#### Forgetting Data

You can remove context data selectively:

```php
$scope->forget('foo'); // Remove only one attribute
$scope->clear(); // Remove whole scope
App::context()->flush(); // Remove whole context
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
    'scope' => 'context.app',
    'path' => 'foo',
    'current' => 'bar',
    'previous' => null,
]);
```

## Available Hooks

| Hook             | Description                                 | Data                                                                                                           |
| ---------------- | ------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `context:update` | Triggered when the context is updated       | `scope`: Context group <br> `path`: Updated path <br> `current`: Current value <br> `previous`: Previous value |
| `context:forget` | Triggered when a context item is forgotten  | `scope`: Context group <br> `path`: Updated path <br> `current`: Current value <br> `previous`: Previous value |
| `context:clear`  | Triggered when specific group is cleared    | `scope`: Context group <br> `path`: Updated path <br> `current`: Current value <br> `previous`: Previous value |
| `context:flush`  | Triggered when the whole context is flushed | `scope`: Context group <br> `path`: Updated path <br> `current`: Current value <br> `previous`: Previous value |
| `basecamp:send`  | Triggered when the request is made | `request`: `\Rosalana\Core\Services\Basecamp\Request` <br> `response`: `\Illuminate\Http\Client\Response`
| `outpost:send`  | Triggered when the message is send | `message`: `\Rosalana\Core\Services\Outpost\Message`
| `outpost:receive`  | Triggered when the message is received | `message`: `\Rosalana\Core\Services\Outpost\Message`
| `internal:verify`  | Triggered when the internal route is verified | `request`: `\Illuminate\Http\Request`

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

- **Plugin infrastructure**
- **Realtime WebSocket integration**
- **Package actions**: Allow packages to define custom actions that can be triggered via CLI.

Stay tuned — we're actively shaping the foundation of the Rosalana ecosystem.

## Bugs

_It looks like there are no known bugs at the moment._

## License

Rosalana Core is open-source under the [MIT license](/LICENCE), allowing you to freely use, modify, and distribute it with minimal restrictions.

You may not be able to use our systems but you can use our code to build your own.

For details on how to contribute or how the Rosalana ecosystem is maintained, please refer to each repository’s individual guidelines.

**Questions or feedback?**

Feel free to open an issue or contribute with a pull request. Happy coding with Rosalana!
