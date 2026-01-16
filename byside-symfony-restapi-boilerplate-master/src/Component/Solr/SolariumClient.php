<?php

namespace App\Component\Solr;

use Solarium\Client;
use Solarium\Core\Client\Adapter\Http;
use Symfony\Component\EventDispatcher\EventDispatcher;

// TODO: improve configuration management
class SolariumClient
{
    protected array $cluster = [];
    protected ?string $currentNode = null;
    protected ?Client $client = null;

    public function __construct(string $cluster)
    {
        $this->cluster = explode(',', $cluster);
    }

    private function getClient($collection): Client
    {
        if (!$this->client instanceof Client) {
            $this->client = new Client(
                new Http(),
                new EventDispatcher(),
                $this->getConfig($collection)
            );
        }

        return $this->client;
    }

    private function getConfig($collection): array
    {
        $this->randomizeNode();

        $hostPort = explode(':', (string) $this->currentNode);

        return [
            'endpoint' => [
                'node' => [
                    'scheme' => 'http', // or https
                    'host' => $hostPort[0],
                    'port' => $hostPort[1],
                    'path' => '/',
                    // 'context' => 'solr', # only necessary to set if not the default 'solr'
                    'collection' => $collection,
                ],
            ],
        ];
    }

    private function randomizeNode(): void
    {
        $node = $this->cluster[array_rand($this->cluster, 1)];

        if ($node === $this->currentNode && count($this->cluster) > 1) {
            $this->randomizeNode();
        } else {
            $this->currentNode = $node;
        }
    }

    public function ping($collection): array|false
    {
        $ping = $this->getClient($collection)->createPing();

        try {
            $result = $this->getClient($collection)->ping($ping);

            return $result->getData();
        } catch (\Exception) {
            return false;
        }
    }
}
