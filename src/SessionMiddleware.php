<?php

declare(strict_types=1);

/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under MIT license by Niko Granö.
 *
 * @copyright Niko Granö <niko9911@ironlions.fi> (https://granö.fi)
 *
 */

namespace Niko9911\React\Middleware;

use HansOtt\PSR7Cookies\RequestCookies;
use HansOtt\PSR7Cookies\SetCookie;
use Niko9911\React\Middleware\Session\Id;
use Niko9911\React\Middleware\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Cache\CacheInterface;
use React\Promise\PromiseInterface;
use Throwable;
use function React\Promise\resolve;

class SessionMiddleware
{
    public const ATTRIBUTE_NAME = 'niko9911.react.middleware.session';

    /**
     * @var string
     */
    protected $cookieName;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var array
     */
    protected $cookieParams;

    /**
     * @var Id
     */
    protected $sessionId;

    /**
     * @param string         $cookieName
     * @param CacheInterface $cache
     * @param int            $expires
     * @param string         $path
     * @param string         $domain
     * @param bool           $secure
     * @param bool           $httpOnly
     * @param Id|null        $sessionId
     */
    public function __construct(
        string $cookieName,
        CacheInterface $cache,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = false,
        ?Id $sessionId = null
    ) {
        $this->cookieName = $cookieName;
        $this->cache = $cache;
        $this->cookieParams = [$expires, $path, $domain, $secure, $httpOnly];
        $this->sessionId = $sessionId ?? new Id\Random();
    }

    /**
     * @param ServerRequestInterface $request
     * @param callable               $next
     *
     * @return PromiseInterface
     */
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        return $this->fetchSessionFromRequest($request)->then(function (Session $session) use ($next, $request) {
            $request = $request->withAttribute(self::ATTRIBUTE_NAME, $session);

            return resolve(
                $next($request)
            )->then(function (ResponseInterface $response) use ($session) {
                return $this->updateCache($session)->then(function () use ($response) {
                    return $response;
                });
            })->then(function ($response) use ($session) {
                return $this->getCookie($session)->addToResponse($response);
            });
        });
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return PromiseInterface
     */
    protected function fetchSessionFromRequest(ServerRequestInterface $request): PromiseInterface
    {
        $id = '';
        $cookies = RequestCookies::createFromRequest($request);

        try {
            if (!$cookies->has($this->cookieName)) {
                return resolve(new Session($id, [], $this->sessionId));
            }
            $id = $cookies->get($this->cookieName)->getValue();

            return $this->fetchSessionDataFromCache($id)->then(function (array $sessionData) use ($id) {
                return new Session($id, $sessionData, $this->sessionId);
            });
        } catch (Throwable $et) {
            // Do nothing, only a not found will be thrown so generating our own id now
        }

        return resolve(new Session($id, [], $this->sessionId));
    }

    /**
     * @param string $id
     *
     * @return PromiseInterface
     */
    protected function fetchSessionDataFromCache(string $id): PromiseInterface
    {
        if ('' === $id) {
            return resolve([]);
        }

        return $this->cache->get($id)->then(function ($result) {
            if (null === $result) {
                return resolve([]);
            }

            return $result;
        }, function () {
            return resolve([]);
        });
    }

    /**
     * @param Session $session
     *
     * @return PromiseInterface
     */
    protected function updateCache(Session $session): PromiseInterface
    {
        foreach ($session->getOldIds() as $oldId) {
            $this->cache->delete($oldId);
        }

        if ($session->isActive()) {
            return resolve($this->cache->set($session->getId(), $session->getContents(), $this->cookieParams[0]));
        }

        return resolve();
    }

    /**
     * @param Session $session
     *
     * @return SetCookie
     *
     * @throws \HansOtt\PSR7Cookies\InvalidArgumentException
     */
    protected function getCookie(Session $session): SetCookie
    {
        $cookieParams = $this->cookieParams;

        if ($session->isActive()) {
            // Only set time when expires is set in the future
            if ($cookieParams[0] > 0) {
                $cookieParams[0] += \time();
            }

            return new SetCookie($this->cookieName, $session->getId(), ...$cookieParams);
        }
        unset($cookieParams[0]);

        return SetCookie::thatDeletesCookie($this->cookieName, ...$cookieParams);
    }
}
