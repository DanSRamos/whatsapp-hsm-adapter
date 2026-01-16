<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

interface CacheInterface
{
    /**
     * Recupera um valor do cache
     */
    public function get(string $key): mixed;

    /**
     * Armazena um valor no cache
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void;

    /**
     * Remove um valor do cache
     */
    public function delete(string $key): void;

    /**
     * Verifica se uma chave existe no cache
     */
    public function has(string $key): bool;

    /**
     * Limpa todo o cache
     */
    public function clear(): void;
}
