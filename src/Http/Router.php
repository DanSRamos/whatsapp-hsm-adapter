<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Simple HTTP router implementation
 */
class Router implements RouterInterface
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    /**
     * Add a route to the router
     */
    public function addRoute(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $this->routes[$method][$path] = $handler;
    }

    /**
     * Dispatch a request to the appropriate handler
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        // Try exact match first
        if (isset($this->routes[$method][$path])) {
            return $this->routes[$method][$path]($request);
        }

        // Try pattern matching for routes with parameters
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $routePath => $handler) {
                $pattern = $this->convertRouteToRegex($routePath);
                if (preg_match($pattern, $path, $matches)) {
                    // Extract route parameters
                    $params = $this->extractParams($routePath, $path);
                    $request = $request->withAttribute('routeParams', $params);
                    return $handler($request);
                }
            }
        }

        // Route not found
        return $this->notFoundResponse();
    }

    /**
     * Convert route path to regex pattern
     */
    private function convertRouteToRegex(string $route): string
    {
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route);
        return '#^' . $pattern . '$#';
    }

    /**
     * Extract parameters from path based on route pattern
     */
    private function extractParams(string $routePath, string $actualPath): array
    {
        $pattern = $this->convertRouteToRegex($routePath);
        if (preg_match($pattern, $actualPath, $matches)) {
            // Filter out numeric keys, keep only named parameters
            return array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);
        }
        return [];
    }

    /**
     * Create a 404 Not Found response
     */
    private function notFoundResponse(): ResponseInterface
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Route not found'
            ]
        ], 404);
    }
}
