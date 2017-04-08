<h1 align="center">Centrifuge Broadcaster for Laravel 5</h1>

<p align="center">
<a href="https://travis-ci.org/LaraComponents/centrifuge-broadcaster"><img src="https://travis-ci.org/LaraComponents/centrifuge-broadcaster.svg?branch=master" alt="Build Status"></a>
<a href="https://github.com/LaraComponents/centrifuge-broadcaster/releases"><img src="https://img.shields.io/github/release/LaraComponents/centrifuge-broadcaster.svg?style=flat-square" alt="Latest Version"></a>
<a href="https://scrutinizer-ci.com/g/LaraComponents/centrifuge-broadcaster"><img src="https://img.shields.io/scrutinizer/g/LaraComponents/centrifuge-broadcaster.svg?style=flat-square" alt="Quality Score"></a>
<a href="https://styleci.io/repos/77400544"><img src="https://styleci.io/repos/77400544/shield" alt="StyleCI"></a>
<a href="https://packagist.org/packages/LaraComponents/centrifuge-broadcaster"><img src="https://img.shields.io/packagist/dt/LaraComponents/centrifuge-broadcaster.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://github.com/LaraComponents/centrifuge-broadcaster/blob/master/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Software License"></a>
</p>

## Introduction
Centrifuge broadcaster for laravel >= 5.3

## Requirements

- PHP 5.6.4+ or newer
- Laravel 5.3 or newer
- Centrifugo Server 1.6.1 or newer (see [here](https://github.com/centrifugal/centrifugo))

## Installation

Require this package with composer:

```bash
composer require laracomponents/centrifuge-broadcaster
```

Open your config/app.php and add the following to the providers array:

```php
'providers' => [
    // ...
    LaraComponents\Centrifuge\CentrifugeServiceProvider::class,

    // And uncomment BroadcastServiceProvider
    App\Providers\BroadcastServiceProvider::class,
],
```

Open your config/broadcasting.php and add the following to it:

```php
'connections' => [
    'centrifuge' => [
        'driver' => 'centrifuge',
        'secret' => env('CENTRIFUGE_SECRET'), // you secret key
        'url' => env('CENTRIFUGE_URL', 'http://localhost:8000'), // centrifuge api url
        'redis_api' => env('CENTRIFUGE_REDIS_API', false), // enable or disable Redis API
        'redis_connection' => env('CENTRIFUGE_REDIS_CONNECTION', 'default'), // name of redis connection
        'redis_prefix' => env('CENTRIFUGE_REDIS_PREFIX', 'centrifugo'), // prefix name for queue in Redis
        'redis_num_shards' => env('CENTRIFUGE_REDIS_NUM_SHARDS', 0), // number of shards for redis API queue
    ],
    // ...
],
```

For the redis configuration, add a new connection in config/database.php

```php
'redis' => [
    'centrifuge' => [
        'host' => 'localhost',
        'password' => null,
        'port' => 6379,
        'database' => 0,
    ],
    // ...
],
```

and open your config/broadcasting.php and set the redis_connection parameter

```php
'connections' => [
    'centrifuge' => [
        // ...
        'redis_connection' => 'centrifuge',
        // ...
    ],
],
```

You can also add a configuration to your .env file:

```
CENTRIFUGE_SECRET=very-long-secret-key
CENTRIFUGE_URL=http://localhost:8000
CENTRIFUGE_REDIS_API=false
CENTRIFUGE_REDIS_CONNECTION=default
CENTRIFUGE_REDIS_PREFIX=centrifugo
CENTRIFUGE_REDIS_NUM_SHARDS=0
```

Do not forget to install the broadcast driver

```
BROADCAST_DRIVER=centrifuge
```

## Basic Usage

To configure the Centrifugo server, read the [official documentation](https://fzambia.gitbooks.io/centrifugal/content)

For broadcasting events, see the [official documentation of laravel](https://laravel.com/docs/5.3/broadcasting)

A simple example of using the client:

```php
<?php

namespace App\Http\Controllers;

use LaraComponents\Centrifuge\Centrifuge;

class ExampleController extends Controller
{
    public function home(Centrifuge $centrifuge)
    {
        // Send message into channel
        $centrifuge->publish('channel-name', [
            'key' => 'value'
        ]);

        // Generate token
        $token = $centrifuge->generateToken('user id', 'timestamp', 'info');

        // Generate api sign
        $apiSign = $centrifuge->generateApiSign('data');

        // ...
    }
}
```

### Available methods

| Name | Description |
|------|-------------|
| publish(string $channel, array $data, string $client = null) | Send message into channel. |
| broadcast(array $channels, array $data, string $client = null) | Send message into multiple channel. |
| presence(string $channel) | Get channel presence information (all clients currently subscribed on this channel). |
| history(string $channel) | Get channel history information (list of last messages sent into channel). |
| unsubscribe(string $user_id, string $channel = null) | Unsubscribe user from channel. |
| disconnect(string $user_id) | Disconnect user by its ID. |
| channels() | Get channels information (list of currently active channels). |
| stats() | Get stats information about running server nodes. |
| generateToken(string $userOrClient, string $timestampOrChannel, string $info = "")  | Generate token. |
| generateApiSign(string $data) | Generate api sign. |

## License

The MIT License (MIT). Please see [License File](https://github.com/LaraComponents/centrifuge-broadcaster/blob/master/LICENSE) for more information.
