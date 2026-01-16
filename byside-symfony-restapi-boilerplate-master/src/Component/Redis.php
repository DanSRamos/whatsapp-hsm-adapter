<?php

namespace App\Component;

class Redis
{
    private \Redis $client;
    private \RedisCluster $clientCluster;

    public function __construct(string $host)
    {
        if (strtoupper((string) $_ENV['APP_ZONE']) === 'DEVEL') {
            $hosts = explode(':', $host);
            $this->client = new \Redis();
            $this->client->connect($hosts[0], $hosts[1]);
        } else {
            $hosts = explode(',', $host);
            $this->clientCluster = new \RedisCluster(null, $hosts);
        }
    }

    /**
     * Get a Redis Client.
     */
    public function getClient(): \Redis|\RedisCluster
    {
        if (strtoupper((string) $_ENV['APP_ZONE']) === 'DEVEL') {
            return $this->client;
        }

        return $this->clientCluster;
    }
}
