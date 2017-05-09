<?php


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class FastRouteMiddleware implements MiddlewareInterface
{
    private $routes;
    private $notFoundCallable;

    public function __construct(array $routes, $notFoundCallable)
    {
        $this->routes = $routes;
        $this->notFoundCallable = $notFoundCallable;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $method = strtoupper($request->getMethod());
        $requestUri = $request->getUri();
        $query = $requestUri->getQuery();
        $uri = $requestUri->getPath() . ($query ? '?' . $query : '');

        // Process routes
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route[0], $route[1], $route[2]);
            }
        });

        // Dispatch request
        $routeInfo = $dispatcher->dispatch($method, $uri);

        // Check found
        if ($routeInfo[0] !== \FastRoute\Dispatcher::FOUND) {
            return call_user_func_array($this->notFoundCallable, []);
        }

        // Handle and return created Response
        // no delegate next here
        return call_user_func_array($routeInfo[1], $routeInfo[2]);
    }
}