<?php

namespace FastRouteMiddleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class Router implements MiddlewareInterface
{
    const HANDLER_ATTRIBUTE = 'request-handler';

    private $routes;
    private $notFoundCallable;

    public function __construct(array $routes, $notFoundCallable, $processResultCallable = null)
    {
        $this->routes = $routes;
        $this->notFoundCallable = $notFoundCallable;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        // Process routes
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route[0], $route[1], $route[2]);
            }
        });

        // Dispatch request
        $method = strtoupper($request->getMethod());
        $routeInfo = $dispatcher->dispatch($method, $request->getUri()->getPath());

        // Check found
        if ($routeInfo[0] !== \FastRoute\Dispatcher::FOUND) {
            return call_user_func_array($this->notFoundCallable, []);
        }

        // Add params to Request attributes
        foreach ($routeInfo[2] as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }
        $request = $request->withAttribute(self::HANDLER_ATTRIBUTE, $routeInfo[1]);

        return $delegate->process($request);
    }
}