<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http;

use Psr\Http\Message\StreamInterface;

/**
 * Simple string-based stream implementation
 */
class StringStream implements StreamInterface
{
    private string $content;
    private int $position = 0;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void
    {
        // No-op for string stream
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->content);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->position = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => strlen($this->content) + $offset,
            default => throw new \InvalidArgumentException('Invalid whence value'),
        };
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new \RuntimeException('Stream is not writable');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $result = substr($this->content, $this->position, $length);
        $this->position += strlen($result);
        return $result;
    }

    public function getContents(): string
    {
        return substr($this->content, $this->position);
    }

    public function getMetadata(?string $key = null)
    {
        return $key === null ? [] : null;
    }
}
