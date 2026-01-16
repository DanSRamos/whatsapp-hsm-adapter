<?php

declare(strict_types=1);

use WhatsApp\Adapter\Http\Controllers\HealthController;
use WhatsApp\Adapter\Services\CacheInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

beforeEach(function () {
    $this->database = Mockery::mock(PDO::class);
    $this->cache = Mockery::mock(CacheInterface::class);
    $this->httpClient = Mockery::mock(ClientInterface::class);
    $this->logger = Mockery::mock(LoggerInterface::class);
    
    $this->config = [
        'providers' => [
            'infobip' => [
                'api_key' => 'test_key',
                'base_url' => 'https://api.infobip.com',
            ],
            'twilio' => [
                'account_sid' => 'test_sid',
                'auth_token' => 'test_token',
            ],
        ],
    ];

    $this->controller = new HealthController(
        $this->database,
        $this->cache,
        $this->httpClient,
        $this->config,
        $this->logger
    );

    $this->request = Mockery::mock(ServerRequestInterface::class);
});

afterEach(function () {
    Mockery::close();
});

test('health check returns healthy when all services are OK', function () {
    // Mock database check
    $stmt = Mockery::mock(PDOStatement::class);
    $stmt->shouldReceive('fetch')->andReturn(['1' => 1]);
    $this->database->shouldReceive('query')
        ->with('SELECT 1')
        ->andReturn($stmt);

    // Mock Redis check
    $this->cache->shouldReceive('set')->once();
    $this->cache->shouldReceive('get')->once()->andReturn('test');
    $this->cache->shouldReceive('delete')->once();

    // Mock Infobip provider check
    $infobipResponse = new Response(200);
    $this->httpClient->shouldReceive('get')
        ->with(
            'https://api.infobip.com/whatsapp/1/senders',
            Mockery::any()
        )
        ->andReturn($infobipResponse);

    // Mock Twilio provider check
    $twilioResponse = new Response(200);
    $this->httpClient->shouldReceive('get')
        ->with(
            Mockery::pattern('/api\.twilio\.com/'),
            Mockery::any()
        )
        ->andReturn($twilioResponse);

    $response = $this->controller->check($this->request);

    expect($response->getStatusCode())->toBe(200);
    
    $body = json_decode((string) $response->getBody(), true);
    expect($body['status'])->toBe('healthy');
    expect($body['checks']['database']['healthy'])->toBeTrue();
    expect($body['checks']['redis']['healthy'])->toBeTrue();
    expect($body['checks']['providers']['healthy'])->toBeTrue();
});

test('health check returns unhealthy when database fails', function () {
    // Mock database failure
    $this->database->shouldReceive('query')
        ->with('SELECT 1')
        ->andThrow(new PDOException('Connection failed'));

    $this->logger->shouldReceive('error')->once();

    // Mock Redis check (successful)
    $this->cache->shouldReceive('set')->once();
    $this->cache->shouldReceive('get')->once()->andReturn('test');
    $this->cache->shouldReceive('delete')->once();

    // Mock provider checks (successful)
    $infobipResponse = new Response(200);
    $this->httpClient->shouldReceive('get')
        ->with(
            'https://api.infobip.com/whatsapp/1/senders',
            Mockery::any()
        )
        ->andReturn($infobipResponse);

    $twilioResponse = new Response(200);
    $this->httpClient->shouldReceive('get')
        ->with(
            Mockery::pattern('/api\.twilio\.com/'),
            Mockery::any()
        )
        ->andReturn($twilioResponse);

    $response = $this->controller->check($this->request);

    expect($response->getStatusCode())->toBe(503);
    
    $body = json_decode((string) $response->getBody(), true);
    expect($body['status'])->toBe('unhealthy');
    expect($body['checks']['database']['healthy'])->toBeFalse();
    expect($body['checks']['database']['message'])->toBe('Database connection failed');
});

test('health check returns unhealthy when Redis fails', function () {
    // Mock database check (successful)
    $stmt = Mockery::mock(PDOStatement::class);
    $stmt->shouldReceive('fetch')->andReturn(['1' => 1]);
    $this->database->shouldReceive('query')
        ->with('SELECT 1')
        ->andReturn($stmt);

    // Mock Redis failure
    $this->cache->shouldReceive('set')
        ->andThrow(new RuntimeException('Redis connection failed'));

    $this->logger->shouldReceive('error')->once();

    // Mock provider checks (successful)
    $infobipResponse = new Response(200);
    $this->httpClient->shouldReceive('get')
        ->with(
            'https://api.infobip.com/whatsapp/1/senders',
            Mockery::any()
        )
        ->andReturn($infobipResponse);

    $twilioResponse = new Response(200);
    $this->httpClient->shouldReceive('get')
        ->with(
            Mockery::pattern('/api\.twilio\.com/'),
            Mockery::any()
        )
        ->andReturn($twilioResponse);

    $response = $this->controller->check($this->request);

    expect($response->getStatusCode())->toBe(503);
    
    $body = json_decode((string) $response->getBody(), true);
    expect($body['status'])->toBe('unhealthy');
    expect($body['checks']['redis']['healthy'])->toBeFalse();
    expect($body['checks']['redis']['message'])->toBe('Redis connection failed');
});

test('health check returns unhealthy when provider fails', function () {
    // Mock database check (successful)
    $stmt = Mockery::mock(PDOStatement::class);
    $stmt->shouldReceive('fetch')->andReturn(['1' => 1]);
    $this->database->shouldReceive('query')
        ->with('SELECT 1')
        ->andReturn($stmt);

    // Mock Redis check (successful)
    $this->cache->shouldReceive('set')->once();
    $this->cache->shouldReceive('get')->once()->andReturn('test');
    $this->cache->shouldReceive('delete')->once();

    // Mock Infobip provider failure
    $this->httpClient->shouldReceive('get')
        ->with(
            'https://api.infobip.com/whatsapp/1/senders',
            Mockery::any()
        )
        ->andThrow(new RuntimeException('Connection timeout'));

    $this->logger->shouldReceive('warning')->once();

    // Mock Twilio provider check (successful)
    $twilioResponse = new Response(200);
    $this->httpClient->shouldReceive('get')
        ->with(
            Mockery::pattern('/api\.twilio\.com/'),
            Mockery::any()
        )
        ->andReturn($twilioResponse);

    $response = $this->controller->check($this->request);

    expect($response->getStatusCode())->toBe(503);
    
    $body = json_decode((string) $response->getBody(), true);
    expect($body['status'])->toBe('unhealthy');
    expect($body['checks']['providers']['healthy'])->toBeFalse();
    expect($body['checks']['providers']['providers']['infobip']['healthy'])->toBeFalse();
});

test('health check includes timestamp in response', function () {
    // Mock all checks as successful
    $stmt = Mockery::mock(PDOStatement::class);
    $stmt->shouldReceive('fetch')->andReturn(['1' => 1]);
    $this->database->shouldReceive('query')->andReturn($stmt);

    $this->cache->shouldReceive('set')->once();
    $this->cache->shouldReceive('get')->once()->andReturn('test');
    $this->cache->shouldReceive('delete')->once();

    $infobipResponse = new Response(200);
    $this->httpClient->shouldReceive('get')
        ->with('https://api.infobip.com/whatsapp/1/senders', Mockery::any())
        ->andReturn($infobipResponse);

    $twilioResponse = new Response(200);
    $this->httpClient->shouldReceive('get')
        ->with(Mockery::pattern('/api\.twilio\.com/'), Mockery::any())
        ->andReturn($twilioResponse);

    $response = $this->controller->check($this->request);

    $body = json_decode((string) $response->getBody(), true);
    expect($body)->toHaveKey('timestamp');
    expect($body['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});
