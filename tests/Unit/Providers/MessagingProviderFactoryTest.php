<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use WhatsApp\Adapter\Providers\Meta\MetaProvider;
use WhatsApp\Adapter\Providers\MessagingProviderFactory;

class MessagingProviderFactoryTest extends TestCase
{
    private Client $httpClient;
    private NullLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = new Client();
        $this->logger = new NullLogger();
    }

    public function testCreateMetaProviderWithValidConfig(): void
    {
        $config = [
            'default_provider' => 'meta',
            'providers' => [
                'meta' => [
                    'page_access_token' => str_repeat('a', 200), // Valid long token
                    'app_id' => '123456789',
                    'app_secret' => 'abcdef1234567890abcdef1234567890',
                    'page_id' => '987654321',
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);
        $provider = $factory->getProvider('meta');

        $this->assertInstanceOf(MetaProvider::class, $provider);
        $this->assertEquals('meta', $provider->getName());
    }

    public function testCreateInstagramProviderAlias(): void
    {
        $config = [
            'providers' => [
                'instagram' => [
                    'page_access_token' => str_repeat('a', 200),
                    'app_id' => '123456789',
                    'app_secret' => 'abcdef1234567890abcdef1234567890',
                    'page_id' => '987654321',
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);
        $provider = $factory->getProvider('instagram');

        $this->assertInstanceOf(MetaProvider::class, $provider);
    }

    public function testCreateMessengerProviderAlias(): void
    {
        $config = [
            'providers' => [
                'messenger' => [
                    'page_access_token' => str_repeat('a', 200),
                    'app_id' => '123456789',
                    'app_secret' => 'abcdef1234567890abcdef1234567890',
                    'page_id' => '987654321',
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);
        $provider = $factory->getProvider('messenger');

        $this->assertInstanceOf(MetaProvider::class, $provider);
    }

    public function testThrowsExceptionWhenMissingPageAccessToken(): void
    {
        $config = [
            'providers' => [
                'meta' => [
                    // Missing page_access_token
                    'app_id' => '123456789',
                    'app_secret' => 'abcdef1234567890abcdef1234567890',
                    'page_id' => '987654321',
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Meta provider configuration is incomplete');
        $this->expectExceptionMessage('page_access_token');

        $factory->getProvider('meta');
    }

    public function testThrowsExceptionWhenMissingAppId(): void
    {
        $config = [
            'providers' => [
                'meta' => [
                    'page_access_token' => str_repeat('a', 200),
                    // Missing app_id
                    'app_secret' => 'abcdef1234567890abcdef1234567890',
                    'page_id' => '987654321',
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Meta provider configuration is incomplete');
        $this->expectExceptionMessage('app_id');

        $factory->getProvider('meta');
    }

    public function testThrowsExceptionWhenMissingAppSecret(): void
    {
        $config = [
            'providers' => [
                'meta' => [
                    'page_access_token' => str_repeat('a', 200),
                    'app_id' => '123456789',
                    // Missing app_secret
                    'page_id' => '987654321',
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Meta provider configuration is incomplete');
        $this->expectExceptionMessage('app_secret');

        $factory->getProvider('meta');
    }

    public function testThrowsExceptionWhenMissingPageId(): void
    {
        $config = [
            'providers' => [
                'meta' => [
                    'page_access_token' => str_repeat('a', 200),
                    'app_id' => '123456789',
                    'app_secret' => 'abcdef1234567890abcdef1234567890',
                    // Missing page_id
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Meta provider configuration is incomplete');
        $this->expectExceptionMessage('page_id');

        $factory->getProvider('meta');
    }

    public function testThrowsExceptionWhenMissingVerifyToken(): void
    {
        $config = [
            'providers' => [
                'meta' => [
                    'page_access_token' => str_repeat('a', 200),
                    'app_id' => '123456789',
                    'app_secret' => 'abcdef1234567890abcdef1234567890',
                    'page_id' => '987654321',
                    // Missing verify_token
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Meta provider configuration is incomplete');
        $this->expectExceptionMessage('verify_token');

        $factory->getProvider('meta');
    }

    public function testThrowsExceptionWhenPageAccessTokenTooShort(): void
    {
        $config = [
            'providers' => [
                'meta' => [
                    'page_access_token' => 'short_token', // Too short
                    'app_id' => '123456789',
                    'app_secret' => 'abcdef1234567890abcdef1234567890',
                    'page_id' => '987654321',
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid META_PAGE_ACCESS_TOKEN format');

        $factory->getProvider('meta');
    }

    public function testThrowsExceptionWhenPageIdNotNumeric(): void
    {
        $config = [
            'providers' => [
                'meta' => [
                    'page_access_token' => str_repeat('a', 200),
                    'app_id' => '123456789',
                    'app_secret' => 'abcdef1234567890abcdef1234567890',
                    'page_id' => 'not_numeric', // Invalid format
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid META_PAGE_ID format');

        $factory->getProvider('meta');
    }

    public function testThrowsExceptionWhenAppIdNotNumeric(): void
    {
        $config = [
            'providers' => [
                'meta' => [
                    'page_access_token' => str_repeat('a', 200),
                    'app_id' => 'not_numeric', // Invalid format
                    'app_secret' => 'abcdef1234567890abcdef1234567890',
                    'page_id' => '987654321',
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid META_APP_ID format');

        $factory->getProvider('meta');
    }

    public function testThrowsExceptionWhenAppSecretInvalidFormat(): void
    {
        $config = [
            'providers' => [
                'meta' => [
                    'page_access_token' => str_repeat('a', 200),
                    'app_id' => '123456789',
                    'app_secret' => 'invalid_secret', // Not 32 hex chars
                    'page_id' => '987654321',
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid META_APP_SECRET format');

        $factory->getProvider('meta');
    }

    public function testProviderInstanceIsCached(): void
    {
        $config = [
            'providers' => [
                'meta' => [
                    'page_access_token' => str_repeat('a', 200),
                    'app_id' => '123456789',
                    'app_secret' => 'abcdef1234567890abcdef1234567890',
                    'page_id' => '987654321',
                    'verify_token' => 'my_verify_token',
                ],
            ],
        ];

        $factory = new MessagingProviderFactory($config, $this->httpClient, $this->logger);
        
        $provider1 = $factory->getProvider('meta');
        $provider2 = $factory->getProvider('meta');

        // Should return the same instance
        $this->assertSame($provider1, $provider2);
    }
}
