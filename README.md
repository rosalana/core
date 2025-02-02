# Rosalana Core

Rosalana Core is the shared foundation for all applications in the Rosalana ecosystem. Its primary goal is to provide a unified framework of code, structures, and conventions that you can reuse across multiple **Laravel-based projects**.

> For more advanced features that are specific to certain functionalities, Rosalana provides additional packages.

>`rosalana/*` packages are meant to be used in Laravel applications with SPA frontends.

## Table of Contents

- [Features](#features)
    - [Basecamp Connection](#basecamp-connection)
- [Installation](#installation)
- [Configuration](#configuration)
- [May Show in the Future](#may-show-in-the-future)
- [License](#license)


## Features

### Basecamp Connection

> Connect to the central **Rosalana: Basecamp** server

Every package above `rosalana/core` can connect to the central Basecamp server to fetch user data, settings, and more. This connection is managed by the `Rosalana\Core\Services\Basecamp\Manager` class.

Connectivity is established via the `rosalana.basecamp` service, which you can access through the `Basecamp` facade.

It's possible to create custom services that extend the `Basecamp Facade`. This way, you can define specific methods to interact with the Basecamp server.

For example, you can create a service that fetches data about a users from the Basecamp server. You can define the service like this:

```php
use Rosalana\Core\Services\Basecamp\Service;

class UsersService extends Service
{
    public function getUser($id)
    {
        return $this->manager->get("users/{$id}");
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
Basecamp::users()->getUser(1);
```

## Installation

You can install `rosalana/core` via Composer by running the following command:

```bash
composer require rosalana/core
```

After installing the package, you can publish its assets using the following command:

```bash
php artisan rosalana:core:install
```

> **Warning:** This is a one-time operation, don't run it multiple times.

> **Note:** API of commands may change in the future versions.

## Configuration

Inside `rosalana/core`, you’ll find a default configuration file in `config/rosalana.php`. That let you adjust settings.

Connection to the Basecamp server expects the following configuration:

```php
return [
    'basecamp' => [
        'url' => env('ROSALANA_BASECAMP_URL'),
        'secret' => env('ROSALANA_CLIENT_SECRET'),
        'origin' => env('FRONTEND_URL'),
    ],
];
```

## May Show in the Future
- **Basecamp key Decoder:** Decoding the Basecamp access token right in the rosalana/core package.
- **Install/Update Command:** A banch of commands to init rosalana packages and update them. Managing the configuration changes and other stuff. Other packages just register themselves in the install command.

    Commands can handle missmatch of the `rosalana.php` configuration file. They can also handle the scaffolding of the packages.

## License

Rosalana Core is open-source under the [MIT license](/LICENCE), allowing you to freely use, modify, and distribute it with minimal restrictions.

You may not be able to use our systems but you can use our code to build your own.

For details on how to contribute or how the Rosalana ecosystem is maintained, please refer to each repository’s individual guidelines.

**Questions or feedback?**

Feel free to open an issue or contribute with a pull request. Happy coding with Rosalana!