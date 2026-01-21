<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Providers\Infobip\InfobipProvider;
use WhatsApp\Adapter\Providers\Meta\MetaProvider;
use WhatsApp\Adapter\Providers\Twilio\TwilioProvider;

/**
 * Factory for creating messaging provider instances
 * 
 * This factory creates and manages provider instances based on configuration,
 * and can automatically detect the provider from incoming webhooks.
 * Supports WhatsApp (Infobip, Twilio), Instagram, and Facebook Messenger.
 */
class MessagingProviderFactory
{
    /** @var array<string, MessagingProviderInterface> */
    private array $providers = [];

    public function __construct(
        private readonly array $config,
        private readonly ClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Get or create a provider instance
     *
     * @param string|null $providerName The provider name, or null to use default
     * @return MessagingProviderInterface The provider instance
     * @throws \InvalidArgumentException If provider is unknown
     */
    public function getProvider(?string $providerName = null): MessagingProviderInterface
    {
        $providerName = $providerName ?? $this->config['default_provider'] ?? 'infobip';

        if (!isset($this->providers[$providerName])) {
            $this->providers[$providerName] = $this->createProvider($providerName);
        }

        return $this->providers[$providerName];
    }

    /**
     * Create a new provider instance
     *
     * @param string $providerName The provider name
     * @return MessagingProviderInterface The provider instance
     * @throws \InvalidArgumentException If provider is unknown or configuration is invalid
     */
    private function createProvider(string $providerName): MessagingProviderInterface
    {
        if (!isset($this->config['providers'][$providerName])) {
            throw new \InvalidArgumentException("Provider configuration not found: {$providerName}");
        }

        $providerConfig = $this->config['providers'][$providerName];

        return match($providerName) {
            'infobip' => new InfobipProvider(
                $this->httpClient,
                $providerConfig,
                $this->logger
            ),
            'twilio' => new TwilioProvider(
                $this->httpClient,
                $providerConfig,
                $this->logger
            ),
            'instagram', 'meta', 'messenger' => $this->createMetaProvider($providerConfig),
            default => throw new \InvalidArgumentException("Unknown provider: {$providerName}")
        };
    }

    /**
     * Create a Meta provider instance with validation
     *
     * @param array $providerConfig The provider configuration
     * @return MetaProvider The Meta provider instance
     * @throws \InvalidArgumentException If configuration is invalid
     */
    private function createMetaProvider(array $providerConfig): MetaProvider
    {
        // Extract the actual config from the provider config
        $config = $providerConfig['config'] ?? $providerConfig;
        
        // Validate required credentials
        $this->validateMetaConfig($config);

        return new MetaProvider(
            $this->httpClient,
            $config,
            $this->logger
        );
    }

    /**
     * Validate Meta provider configuration
     *
     * @param array $config The configuration to validate
     * @throws \InvalidArgumentException If configuration is invalid
     */
    private function validateMetaConfig(array $config): void
    {
        // Check for required credentials
        $requiredFields = [
            'page_access_token' => 'META_PAGE_ACCESS_TOKEN',
            'app_id' => 'META_APP_ID',
            'app_secret' => 'META_APP_SECRET',
            'page_id' => 'META_PAGE_ID',
            'verify_token' => 'META_VERIFY_TOKEN'
        ];

        $missingFields = [];
        foreach ($requiredFields as $field => $envVar) {
            if (empty($config[$field])) {
                $missingFields[] = "{$field} (env: {$envVar})";
            }
        }

        if (!empty($missingFields)) {
            throw new \InvalidArgumentException(
                'Meta provider configuration is incomplete. Missing required fields: ' . 
                implode(', ', $missingFields) . '. ' .
                'Please configure these in your .env file or config/meta.php'
            );
        }

        // Validate Page Access Token format
        $token = $config['page_access_token'];
        if (!$this->isValidPageAccessToken($token)) {
            throw new \InvalidArgumentException(
                'Invalid META_PAGE_ACCESS_TOKEN format. ' .
                'Page Access Token should be a long string (typically 200+ characters) ' .
                'starting with your Page ID or App ID. ' .
                'Please verify your token at https://developers.facebook.com/tools/debug/accesstoken/'
            );
        }

        // Validate Page ID format (should be numeric)
        if (!is_numeric($config['page_id'])) {
            throw new \InvalidArgumentException(
                'Invalid META_PAGE_ID format. Page ID should be a numeric string. ' .
                'You can find your Page ID at https://www.facebook.com/[your-page]/about'
            );
        }

        // Validate App ID format (should be numeric)
        if (!is_numeric($config['app_id'])) {
            throw new \InvalidArgumentException(
                'Invalid META_APP_ID format. App ID should be a numeric string. ' .
                'You can find your App ID in the Meta App Dashboard at https://developers.facebook.com/apps/'
            );
        }

        // Validate App Secret format (should be 32 hex characters)
        if (!preg_match('/^[a-f0-9]{32}$/i', $config['app_secret'])) {
            throw new \InvalidArgumentException(
                'Invalid META_APP_SECRET format. App Secret should be a 32-character hexadecimal string. ' .
                'You can find your App Secret in the Meta App Dashboard at https://developers.facebook.com/apps/'
            );
        }
    }

    /**
     * Validate Page Access Token format
     *
     * @param string $token The token to validate
     * @return bool True if token format is valid
     */
    private function isValidPageAccessToken(string $token): bool
    {
        // Page Access Tokens are typically long strings (200+ characters)
        // They may start with various prefixes but should be substantial in length
        if (strlen($token) < 50) {
            return false;
        }

        // Token should not contain spaces or special characters that indicate it's not properly set
        if (preg_match('/\s/', $token)) {
            return false;
        }

        // Token should be alphanumeric with possible underscores, hyphens, and pipes
        if (!preg_match('/^[a-zA-Z0-9_\-|]+$/', $token)) {
            return false;
        }

        return true;
    }

    /**
     * Detect provider from incoming webhook request
     * 
     * This method attempts to identify which provider sent the webhook
     * by validating the request against each configured provider.
     *
     * @param ServerRequestInterface $request The HTTP request
     * @return MessagingProviderInterface|null The detected provider, or null if none match
     */
    public function detectProviderFromWebhook(ServerRequestInterface $request): ?MessagingProviderInterface
    {
        foreach ($this->config['providers'] as $name => $config) {
            try {
                $provider = $this->getProvider($name);
                if ($provider->validateWebhook($request)) {
                    $this->logger->info('Detected provider from webhook', [
                        'provider' => $name
                    ]);
                    return $provider;
                }
            } catch (\Throwable $e) {
                $this->logger->debug('Provider validation failed', [
                    'provider' => $name,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->warning('Could not detect provider from webhook');
        return null;
    }

    /**
     * Get all configured provider names
     *
     * @return array<string> Array of provider names
     */
    public function getConfiguredProviders(): array
    {
        return array_keys($this->config['providers'] ?? []);
    }
}
