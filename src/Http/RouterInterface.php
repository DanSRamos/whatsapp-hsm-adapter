<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface for HTTP routing
 */
interface RouterInterface
{
    /**
     * Add a route to the router
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $path Route path (e.g., /api/templates)
     * @param callable $handler Handler function that receives ServerRequestInterface and returns ResponseInterface
     */
    public function addRoute(string $method, string $path, callable $handler): void;

    /**
     * Dispatch a request to the appropriate handler
     *
     * @param ServerRequestInterface $request The HTTP request
     * @return ResponseInterface The HTTP response
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface;
}
