# Guzzle Log Middleware

[![codecov](https://codecov.io/gh/gmponos/guzzle-log-middleware/branch/master/graph/badge.svg)](https://codecov.io/gh/gmponos/guzzle-log-middleware)
[![Total Downloads](https://img.shields.io/packagist/dt/gmponos/guzzle_logger.svg)](https://packagist.org/packages/gmponos/guzzle_logger)
[![Build Status](https://travis-ci.org/gmponos/guzzle-log-middleware.svg?branch=master)](https://travis-ci.org/gmponos/guzzle-log-middleware)
[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/gmponos/monolog-slack/blob/master/LICENSE.md)
[![PHPPackages Rank](http://phppackages.org/p/gmponos/guzzle_logger/badge/rank.svg)](http://phppackages.org/p/gmponos/guzzle_logger)

This is a middleware for [guzzle](https://github.com/guzzle/guzzle) that will help you automatically log every request 
and response using a PSR-3 logger.

The middleware is functional with Guzzle 6.

### Important Notes
- This package is still in version 0.x.x. According to [semantic versioning](https://semver.org/) major changes can occur while
we are still on 0.x.x version. If you use the package for a project that is in production please lock this package in your composer
to a specific version like `^0.3.0`.

- Hopefully 0.8.0 version will be the last unstable. Please share your feedback or star the project if you like it.

## Install

Via Composer

``` bash
$ composer require gmponos/guzzle_logger
```

## Usage

### Simple usage

```php
use GuzzleLogMiddleware\LogMiddleware;
use GuzzleHttp\HandlerStack;

$logger = new Logger();  //A new PSR-3 Logger like Monolog
$stack = HandlerStack::create(); // will create a stack stack with middlewares of guzzle already pushed inside of it.
$stack->push(new LogMiddleware($logger));
$client = new GuzzleHttp\Client([
    'handler' => $stack,
]);
```

From now on each request and response you execute using `$client` object will be logged.
By default the middleware logs every activity with level `DEBUG`.

### Advanced initialization

The signature of the `LogMiddleware` class is the following:

```php
\GuzzleLogMiddleware\LogMiddleware(
    \Psr\Log\LoggerInterface $logger, 
    \GuzzleLogMiddleware\Handler\HandlerInterface $handler = null, 
    bool $onFailureOnly = false, 
    bool $logStatistics = false
);
```

- **logger** - The PSR-3 logger to use for logging.
- **handler** - A HandlerInterface class that will be responsible for logging your request/response. Check Handlers sections.
- **onFailureOnly** - By default the middleware is set to log every request and response. If you wish to log 
the requests and responses only when guzzle returns a rejection set this as true or when an exception occurred. 
Guzzle returns a rejection when (http_errors)[http://docs.guzzlephp.org/en/stable/request-options.html#http-errors] option is set to true. 
- **logStatistics** - If you this option as true then the middleware will also log statistics about the requests.

### Handlers

In order to make the middleware more flexible we allow the developer to initialize it passing a handler. 
A handler must implement a `HandlerInterface` and it will be responsible for logging. 

Let's say that we create the following handler.

```php
<?php
namespace GuzzleLogMiddleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\TransferStats;
use Psr\Log\LoggerInterface;

final class SimpleHandler implements HandlerInterface
{
    public function log(
        LoggerInterface $logger,
        RequestInterface $request,
        ?ResponseInterface $response,
        ?\Exception $exception,
        ?TransferStats $stats,
        array $options
    ): void {
        $logger->debug('Guzzle HTTP request: ' . \GuzzleHttp\Psr7\str($request));
        return;
    }
}
```

We can pass the handler above during construction of the middleware.

```php
<?php
use GuzzleLogMiddleware\LogMiddleware;
use GuzzleHttp\HandlerStack;

$logger = new Logger();  //A new PSR-3 Logger like Monolog
$stack = HandlerStack::create(); // will create a stack stack with middlewares of guzzle already pushed inside of it.
$stack->push(new LogMiddleware($logger, new SimpleHandler()));
$client = new GuzzleHttp\Client([
    'handler' => $stack,
]);
```

If no handler is passed the middleware will initialize it's own handler. At the moment the default one is `MultiRecordArrayHandler`


#### MultiRecordArrayHandler

This is the default handler used from the middleware. This handler uses internally the `FixedStrategy` and logs all request
and responses with level debug. This hanlder adds a separate log entry for each Request, Response, Exception or TransferStats.
The information about each object are added as an array `context` to the log entry.

#### StringHandler

This handler uses internally the `FixedStrategy` and logs all request and responses with level debug. You can initialize this handler
with a custom strategy. This handler adds a separate log entry for each Request, Response, Exception or TransferStats.
The handler converts the objects to strings and the information about each object are added to the `message` of the log entry.

### Log Level Strategies

#### FixedStrategy

You can use this strategy to log each HTTP Message with a specific level.

#### StatusCodeStrategy

You can use this strategy to log each HTTP Response with a specific level depending on the status code of the Response.

```php
$strategy = new StatusCodeStrategy(
    LogLevel::INFO, // Default level used for requests or for responses that status code are not set with a different level.
    LogLevel::CRITICAL // Default level used for exceptions.
);
$strategy->setLevel(404, LogLevel::WARNING);
$multiRecordArrayHandler = new MultiRecordArrayHandler($strategy);

$logger = new Logger();  //A new PSR-3 Logger like Monolog
$stack = HandlerStack::create(); // will create a stack stack with middlewares of guzzle already pushed inside of it.
$stack->push(new LogMiddleware($logger, $multiRecordArrayHandler));
$client = new GuzzleHttp\Client([
    'handler' => $stack,
]);
``` 

### Using options on each request

 You can set on each request options about your log.
 
 ```php
 $client->get('/', [
     'log' => [
         'on_exception_only' => true,
         'statistics' => true,
     ]
 ]);
 ```

- ``on_exception_only`` Do not log anything unless if the response status code is above the threshold.
- ``statistics`` if the `on_exception_only` option/variable is true and this is also true the middleware will log statistics about the HTTP call.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Credits

- [George Mponos](gmponos@gmail.com)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Todo
 - Create more handlers to log request/responses
