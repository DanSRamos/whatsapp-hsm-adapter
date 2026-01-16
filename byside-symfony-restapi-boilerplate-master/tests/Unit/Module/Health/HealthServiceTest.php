<?php

namespace Tests\Unit\Module\Health;

use App\Component\Redis;
use App\Module\Health\HealthService;
use App\Module\Health\RabbitMqHealthCheck;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HealthServiceTest extends TestCase
{
    /** @var MockObject||HttpClientInterface */
    private $client;

    /** @var MockObject||Connection */
    private $connection;

    /** @var MockObject||Result */
    private $dbalResult;

    /** @var MockObject||DBALException */
    private $dbalException;

    /** @var MockObject||Redis */
    private $redis;

    /** @var MockObject||RabbitMqHealthCheck */
    private $rabbitMqHealthCheck;

    /** @var MockObject||LoggerInterface */
    private $logger;

    public function setUp(): void
    {
        $this->client = $this->createStub(HttpClientInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->dbalResult = $this->createMock(Result::class);
        $this->dbalException = $this->createMock(DBALException::class);
        $this->redis = $this->createMock(Redis::class);
        $this->rabbitMqHealthCheck = $this->createMock(RabbitMqHealthCheck::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        ClockMock::register(HealthService::class);
        ClockMock::withClockMock(true);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerHealthcheck')]
    public function testHealthcheck(array $output, array $expected, ?string $exception = null): void
    {
        // -- healthCheckMysql
        if (isset($output['connectionException']) && $output['connectionException']) {
            $this->connection
                ->expects($this->once())
                ->method('executeQuery')
                ->willThrowException($this->dbalException);
        } else {
            $this->connection
                ->expects($this->once())
                ->method('executeQuery')
                ->willReturn($this->dbalResult);

            if (isset($output['connectionGetResult'])) {
                $this->dbalResult
                    ->expects($this->once())
                    ->method('fetchOne')
                    ->willReturn($output['connectionGetResult'] ?? 0);
            }
        }

        // -- healthcheckRedis
        /** @var MockObject||\RedisCluster */
        $redisClient = $this->createMock(\RedisCluster::class);

        if ($exception != null) {
            $redisClient
                ->expects($this->once())
                ->method('ping')
                ->willThrowException(new \Exception());
        } else {
            $redisClient
                ->expects($this->once())
                ->method('ping')
                ->willReturn($output['redisPing']);
        }
        $this->redis
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($redisClient);

        // -- healthCheckRabbitMq
        $this->rabbitMqHealthCheck
            ->expects($this->once())
            ->method('healthy')
            ->willReturn($output['rabbitMqHealthy'] ?? true);

        $service = new HealthService(
            $this->client,
            $this->connection,
            $this->redis,
            $this->rabbitMqHealthCheck,
            $this->logger
        );

        $result = $service->healthCheck();
        $this->assertEquals($expected, $result);
    }

    public static function providerHealthcheck()
    {
        $statusSuccess = [
            'status' => 'success',
            'request-time' => 0.0,
        ];
        $statusFailure = [
            'status' => 'failure',
            'request-time' => 0.0,
        ];

        return [
            'healthCheckRabbitMQ -> rabbitMQ success' => [
                'output' => [
                    'connectionIsConnected' => true,
                    'connectionGetResult' => 1,
                    'connectionException' => false,
                    'redisPing' => true,
                    'rabbitMqHealthy' => true,
                ],
                'expected' => [
                    'rabbitmq' => $statusSuccess,
                    'mysql' => $statusSuccess,
                    'redis' => $statusSuccess,
                ],
            ],
            'healthCheckRabbitMQ -> rabbitMQ failure' => [
                'output' => [
                    'connectionIsConnected' => true,
                    'connectionGetResult' => 1,
                    'connectionException' => false,
                    'redisPing' => true,
                    'rabbitMqHealthy' => false,
                ],
                'expected' => [
                    'rabbitmq' => $statusFailure,
                    'mysql' => $statusSuccess,
                    'redis' => $statusSuccess,
                ],
            ],
            'healthCheck -> Successful' => [
                'output' => [
                    'connectionIsConnected' => true,
                    'connectionGetResult' => 1,
                    'connectionException' => false,
                    'redisPing' => true,
                    'rabbitMqHealthy' => true,
                ],
                'expected' => [
                    'rabbitmq' => $statusSuccess,
                    'mysql' => $statusSuccess,
                    'redis' => $statusSuccess,
                ],
            ],
            'healthCheckMysql -> connectionIsConnected false' => [
                'output' => [
                    'connectionIsConnected' => false,
                    'connectionException' => true,
                    'redisPing' => true,
                    'rabbitMqHealthy' => true,
                ],
                'expected' => [
                    'rabbitmq' => $statusSuccess,
                    'mysql' => $statusFailure,
                    'redis' => $statusSuccess,
                ],
            ],
            'healthCheckRedis -> redisPing false' => [
                'output' => [
                    'connectionIsConnected' => true,
                    'connectionGetResult' => 1,
                    'connectionException' => false,
                    'redisPing' => false,
                    'rabbitMqHealthy' => true,
                ],
                'expected' => [
                    'rabbitmq' => $statusSuccess,
                    'mysql' => $statusSuccess,
                    'redis' => $statusFailure,
                ],
            ],
            'healthCheckRedis -> exception' => [
                'output' => [
                    'connectionIsConnected' => true,
                    'connectionGetResult' => 1,
                    'connectionException' => false,
                    'redisPing' => false,
                    'rabbitMqHealthy' => true,
                ],
                'expected' => [
                    'rabbitmq' => $statusSuccess,
                    'mysql' => $statusSuccess,
                    'redis' => $statusFailure,
                ],
                'exception' => 'ERROR',
            ],
        ];
    }
}
