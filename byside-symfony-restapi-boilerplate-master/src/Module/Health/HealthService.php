<?php

namespace App\Module\Health;

use App\Component\Redis;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HealthService
{
    /**
     * Constructor.
     */
    public function __construct(
        HttpClientInterface $client,
        private readonly Connection $connection,
        private readonly Redis $redis,
        private readonly RabbitMqHealthCheck $rabbitMqHealthCheck,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Check the current status of all meteora dependencies.
     */
    public function healthCheck(): array
    {
        return [
            'rabbitmq' => $this->healthCheckRabbitMq(),
            'mysql' => $this->healthCheckMysql(),
            'redis' => $this->healthCheckRedis(),
        ];
    }

    /**
     * Health Check Mysql.
     */
    private function healthCheckMysql(): array
    {
        $start = microtime(true);

        try {
            // Attempt to execute a simple query to check the connection
            $result = $this->connection->executeQuery('SELECT 1')->fetchOne();

            if ($result === 1) {
                return [
                    'status' => 'success',
                    'request-time' => microtime(true) - $start,
                ];
            }
        } catch (Exception $e) {
            $this->logger->error('HealthService :: Database connection error: ' . $e->getMessage());
        }

        return [
            'status' => 'failure',
            'request-time' => microtime(true) - $start,
        ];
    }

    /**
     * Health Check Redis.
     */
    private function healthCheckRedis(): array
    {
        $start = microtime(true);
        $redisClient = $this->redis->getClient();

        try {
            $res = $redisClient->ping('teste');

            if (!$res) {
                return [
                    'status' => 'failure',
                    'request-time' => microtime(true) - $start,
                ];
            }
        } catch (\Exception) {
            $res = $redisClient->setex('atlas:healthcheck:redis', 10, 'ACK');

            if ($res != 'OK') {
                return [
                    'status' => 'failure',
                    'request-time' => microtime(true) - $start,
                ];
            }
        }

        return [
            'status' => 'success',
            'request-time' => microtime(true) - $start,
        ];
    }

    private function healthCheckRabbitMq(): array
    {
        $start = microtime(true);
        $healthy = $this->rabbitMqHealthCheck->healthy();

        return [
            'status' => ($healthy ? 'success' : 'failure'),
            'request-time' => microtime(true) - $start,
        ];
    }
}
