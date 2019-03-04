# ReactPHP Session Middleware
Middleware for implementing PHP Sessions in ReactPHP Http Server.

## Install

Via [composer](http://getcomposer.org):

```shell
composer require niko9911/react-middleware-session
```

## Usage

Register middleware as you normally do. 
Pass cookie name to use.
Pass implementation of React cache or use bridge for PSR cache.


Example:
```php
<?php
declare(strict_types=1);

// Cache must be persisted. Array cache will not work.
// Just abstract Example. You need to implement
// \React\Cache\CacheInterface::class interface.
use Niko9911\Harold\Core\Http\Application\Middleware\SessionMiddleware;$cache = new \React\Cache\ArrayCache();

// Or
// This is just example here. I personally use PSR 
// implementation with bridge and I save all to Redis.
// This will be much easier if using redis other than
// just ReactPHP stuff. You need to require additional package.
// https://packagist.org/packages/niko9911/react-psr-cache-bridge
// https://packagist.org/packages/cache/redis-adapter
$redis = new \Redis();
$redis->connect($host, 6379);
$redis->select(0);
$redis = \Cache\Adapter\Redis\RedisCachePool($redis);
$cache = new \Niko9911\React\Cache\Bridge\ReactPsrCacheBridge($redis);

$session = new \Niko9911\React\Middleware\SessionMiddleware(
    'PHPSESS',
    $cache,
    3600,
    '/',
    '',
    false,
    false,
    new \Niko9911\React\Middleware\Session\Id\Random()
);

new \React\Http\Server(
    [
        $session,
        function (\Psr\Http\Message\ServerRequestInterface $request) {
            /** @var \Niko9911\React\Middleware\Session\Session $session */
            $session = $request->getAttribute(SessionMiddleware::ATTRIBUTE_NAME);
            
            if (!$session->isActive())
            {
                 $session->begin();
            }
            
            echo $session->getId();
            
            return new \React\Http\Response();
        }
    ]
);

```

## License

Licensed under the [MIT license](http://opensource.org/licenses/MIT).
