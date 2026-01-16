<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Simple JSON response implementation
 */
class JsonResponse implements ResponseInterface
{
    private int $statusCode;
    private string $reasonPhrase;
    private array $headers;
    private string $body;
    private string $protocolVersion = '1.1';

    private const REASON_PHRASES = [
        200 => 'OK',
        201 => 'Created',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    public function __construct(array $data, int $statusCode = 200, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->reasonPhrase = self::REASON_PHRASES[$statusCode] ?? 'Unknown';
        $this->headers = array_merge(['Content-Type' => ['application/json']], $headers);
        $this->body = json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $new = clone $this;
        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase ?: (self::REASON_PHRASES[$code] ?? 'Unknown');
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): ResponseInterface
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): ResponseInterface
    {
        $new = clone $this;
        $new->headers[strtolower($name)] = is_array($value) ? $value : [$value];
        return $new;
    }

    public function withAddedHeader(string $name, $value): ResponseInterface
    {
        $new = clone $this;
        $name = strtolower($name);
        $new->headers[$name] = array_merge(
            $new->headers[$name] ?? [],
            is_array($value) ? $value : [$value]
        );
        return $new;
    }

    public function withoutHeader(string $name): ResponseInterface
    {
        $new = clone $this;
        unset($new->headers[strtolower($name)]);
        return $new;
    }

    public function getBody(): StreamInterface
    {
        return new StringStream($this->body);
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        $new = clone $this;
        $new->body = (string) $body;
        return $new;
    }
}
